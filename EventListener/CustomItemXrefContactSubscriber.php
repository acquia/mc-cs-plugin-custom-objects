<?php

declare(strict_types=1);

/*
 * @copyright   2018 Mautic, Inc. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.com
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\CustomObjectsBundle\EventListener;

use Mautic\CoreBundle\EventListener\CommonSubscriber;
use MauticPlugin\CustomObjectsBundle\CustomItemEvents;
use MauticPlugin\CustomObjectsBundle\Event\CustomItemXrefEntityEvent;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Entity\LeadEventLog;
use MauticPlugin\CustomObjectsBundle\Entity\CustomItemXrefContact;
use Doctrine\ORM\EntityManager;
use Mautic\CoreBundle\Helper\UserHelper;
use MauticPlugin\CustomObjectsBundle\Event\CustomItemXrefEntityDiscoveryEvent;
use Doctrine\ORM\NoResultException;
use MauticPlugin\CustomObjectsBundle\Event\CustomItemListQueryEvent;

class CustomItemXrefContactSubscriber extends CommonSubscriber
{
    /**
     * @var EntityManager
     */
    private $entityManager;

    /**
     * @var UserHelper
     */
    private $userHelper;

    /**
     * @param EntityManager $entityManager
     * @param UserHelper    $userHelper
     */
    public function __construct(
        EntityManager $entityManager,
        UserHelper $userHelper
    ) {
        $this->entityManager = $entityManager;
        $this->userHelper    = $userHelper;
    }

    /**
     * @return mixed[]
     */
    public static function getSubscribedEvents(): array
    {
        return [
            CustomItemEvents::ON_CUSTOM_ITEM_LOOKUP_QUERY          => 'onLookupQuery',
            CustomItemEvents::ON_CUSTOM_ITEM_LIST_QUERY            => 'onListQuery',
            CustomItemEvents::ON_CUSTOM_ITEM_LINK_ENTITY_DISCOVERY => 'onEntityLinkDiscovery',
            CustomItemEvents::ON_CUSTOM_ITEM_LINK_ENTITY           => [
                ['saveLink', 1000],
                ['createNewEventLogForLinkedContact', 0],
            ],
            CustomItemEvents::ON_CUSTOM_ITEM_UNLINK_ENTITY         => [
                ['deleteLink', 1000],
                ['createNewEventLogForUnlinkedContact', 0],
            ],
        ];
    }

    /**
     * @param CustomItemListQueryEvent $event
     */
    public function onListQuery(CustomItemListQueryEvent $event): void
    {
        $tableConfig = $event->getTableConfig();
        if ('contact' === $tableConfig->getParameter('filterEntityType') && $tableConfig->getParameter('filterEntityId')) {
            $queryBuilder = $event->getQueryBuilder();
            $queryBuilder->leftJoin('CustomItem.contactReferences', 'CustomItemXrefContact');
            $queryBuilder->andWhere('CustomItemXrefContact.contact = :contactId');
            $queryBuilder->setParameter('contactId', $tableConfig->getParameter('filterEntityId'));
        }
    }

    /**
     * @param CustomItemListQueryEvent $event
     */
    public function onLookupQuery(CustomItemListQueryEvent $event): void
    {
        $tableConfig = $event->getTableConfig();
        if ('contact' === $tableConfig->getParameter('filterEntityType') && $tableConfig->getParameter('filterEntityId')) {
            $queryBuilder = $event->getQueryBuilder();
            $queryBuilder->leftJoin('CustomItem.contactReferences', 'CustomItemXrefContact');
            $queryBuilder->andWhere($queryBuilder->expr()->orX(
                $queryBuilder->expr()->neq('CustomItemXrefContact.contact', $tableConfig->getParameter('filterEntityId')),
                $queryBuilder->expr()->isNull('CustomItemXrefContact.contact')
            ));
        }
    }

    /**
     * @param CustomItemXrefEntityDiscoveryEvent $event
     */
    public function onEntityLinkDiscovery(CustomItemXrefEntityDiscoveryEvent $event): void
    {
        if ('contact' === $event->getEntityType()) {
            try {
                $xRef = $this->getContactXrefEntity($event->getCustomItem()->getId(), $event->getEntityId());
            } catch (NoResultException $e) {
                /** @var Lead $contact */
                $contact = $this->entityManager->getReference(Lead::class, $event->getEntityId());
                $xRef    = new CustomItemXrefContact($event->getCustomItem(), $contact);
            }

            $event->setXrefEntity($xRef);
            $event->stopPropagation();
        }
    }

    /**
     * Save the xref only if it isn't in the entity manager already as it means it was loaded from the database already.
     *
     * @param CustomItemXrefEntityEvent $event
     */
    public function saveLink(CustomItemXrefEntityEvent $event): void
    {
        if ($event->getXref() instanceof CustomItemXrefContact && !$this->entityManager->contains($event->getXref())) {
            $this->entityManager->persist($event->getXref());
            $this->entityManager->flush($event->getXref());
        }
    }

    /**
     * @param CustomItemXrefEntityEvent $event
     */
    public function createNewEventLogForLinkedContact(CustomItemXrefEntityEvent $event): void
    {
        if ($event->getXref() instanceof CustomItemXrefContact) {
            $this->saveEventLog($event->getXref(), 'link');
        }
    }

    /**
     * @param CustomItemXrefEntityEvent $event
     */
    public function deleteLink(CustomItemXrefEntityEvent $event): void
    {
        if ($event->getXref() instanceof CustomItemXrefContact && $this->entityManager->contains($event->getXref())) {
            $this->entityManager->remove($event->getXref());
            $this->entityManager->flush($event->getXref());
        }
    }

    /**
     * @param CustomItemXrefEntityEvent $event
     */
    public function createNewEventLogForUnlinkedContact(CustomItemXrefEntityEvent $event): void
    {
        if ($event->getXref() instanceof CustomItemXrefContact) {
            $this->saveEventLog($event->getXref(), 'unlink');
        }
    }

    /**
     * @param CustomItemXrefContact $xRef
     * @param string                $eventName
     */
    private function saveEventLog(CustomItemXrefContact $xRef, string $eventName): void
    {
        $contact      = $xRef->getContact();
        $customItemId = $xRef->getCustomItem()->getId();
        $eventLog     = $this->initContactEventLog($contact, $eventName, $customItemId);
        $this->entityManager->persist($eventLog);
        $this->entityManager->flush();
    }

    /**
     * @param Lead    $contact
     * @param string  $action
     * @param int     $objectId
     * @param mixed[] $properties
     *
     * @return LeadEventLog
     */
    private function initContactEventLog(Lead $contact, string $action, int $objectId, array $properties = []): LeadEventLog
    {
        $eventLog = new LeadEventLog();
        $eventLog->setBundle('CustomObject');
        $eventLog->setObject('CustomItem');
        $eventLog->setLead($contact);
        $eventLog->setAction($action);
        $eventLog->setObjectId($objectId);
        $eventLog->setUserId($this->userHelper->getUser()->getId());
        $eventLog->setUserName($this->userHelper->getUser()->getName());
        $eventLog->setProperties($properties);

        return $eventLog;
    }

    /**
     * @param int $customItemId
     * @param int $contactId
     *
     * @return CustomItemXrefContact
     *
     * @throws NoResultException if the reference does not exist
     */
    private function getContactXrefEntity(int $customItemId, int $contactId): CustomItemXrefContact
    {
        $queryBuilder = $this->entityManager->createQueryBuilder();
        $queryBuilder->select('cixcont');
        $queryBuilder->from(CustomItemXrefContact::class, 'cixcont');
        $queryBuilder->where('cixcont.customItem = :customItemId');
        $queryBuilder->andWhere('cixcont.contact = :contactId');
        $queryBuilder->setParameter('customItemId', $customItemId);
        $queryBuilder->setParameter('contactId', $contactId);

        return $queryBuilder->getQuery()->getSingleResult();
    }
}
