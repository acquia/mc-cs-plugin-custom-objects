<?php

declare(strict_types=1);

namespace MauticPlugin\CustomObjectsBundle\EventListener;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Mautic\CoreBundle\Helper\UserHelper;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Entity\LeadEventLog;
use MauticPlugin\CustomObjectsBundle\CustomItemEvents;
use MauticPlugin\CustomObjectsBundle\Entity\CustomItem;
use MauticPlugin\CustomObjectsBundle\Entity\CustomItemXrefContact;
use MauticPlugin\CustomObjectsBundle\Entity\CustomObject;
use MauticPlugin\CustomObjectsBundle\Event\CustomItemListDbalQueryEvent;
use MauticPlugin\CustomObjectsBundle\Event\CustomItemListQueryEvent;
use MauticPlugin\CustomObjectsBundle\Event\CustomItemXrefEntityDiscoveryEvent;
use MauticPlugin\CustomObjectsBundle\Event\CustomItemXrefEntityEvent;
use MauticPlugin\CustomObjectsBundle\Repository\CustomItemRepository;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class CustomItemXrefContactSubscriber implements EventSubscriberInterface
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
     * @var CustomItemRepository
     */
    private $customItemRepository;

    public function __construct(
        EntityManager $entityManager,
        UserHelper $userHelper,
        CustomItemRepository $customItemRepository
    ) {
        $this->entityManager        = $entityManager;
        $this->userHelper           = $userHelper;
        $this->customItemRepository = $customItemRepository;
    }

    /**
     * @return mixed[]
     */
    public static function getSubscribedEvents(): array
    {
        return [
            CustomItemEvents::ON_CUSTOM_ITEM_LOOKUP_QUERY          => 'onLookupQuery',
            CustomItemEvents::ON_CUSTOM_ITEM_LIST_ORM_QUERY        => 'onListOrmQuery',
            CustomItemEvents::ON_CUSTOM_ITEM_LIST_DBAL_QUERY       => 'onListDbalQuery',
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

    public function onListDbalQuery(CustomItemListDbalQueryEvent $event): void
    {
        $tableConfig = $event->getTableConfig();
        if ('contact' === $tableConfig->getParameter('filterEntityType') && $tableConfig->getParameter('filterEntityId')) {
            $queryBuilder = $event->getQueryBuilder();
            $queryBuilder->leftJoin(
                CustomItem::TABLE_ALIAS,
                MAUTIC_TABLE_PREFIX.CustomItemXrefContact::TABLE_NAME,
                CustomItemXrefContact::TABLE_ALIAS,
                CustomItem::TABLE_ALIAS.'.id = '.CustomItemXrefContact::TABLE_ALIAS.'.custom_item_id'
            );
            $queryBuilder->andWhere(CustomItemXrefContact::TABLE_ALIAS.'.contact_id = :contactId');
            $queryBuilder->setParameter('contactId', (int) $tableConfig->getParameter('filterEntityId'));
        }
    }

    public function onListOrmQuery(CustomItemListQueryEvent $event): void
    {
        $tableConfig = $event->getTableConfig();
        if ('contact' === $tableConfig->getParameter('filterEntityType') && $tableConfig->getParameter('filterEntityId')) {
            $queryBuilder   = $event->getQueryBuilder();
            $filterEntityId = (int) $tableConfig->getParameter('filterEntityId');

            if ($tableConfig->getParameter('lookup')) {
                $this->customItemRepository->excludeItemsLinkedToContact($queryBuilder, $filterEntityId);
            } else {
                $this->customItemRepository->includeItemsLinkedToContact($queryBuilder, $filterEntityId);
            }
        }
    }

    public function onLookupQuery(CustomItemListQueryEvent $event): void
    {
        $tableConfig = $event->getTableConfig();
        if ('contact' === $tableConfig->getParameter('filterEntityType') && $tableConfig->getParameter('filterEntityId')) {
            $this->customItemRepository->excludeItemsLinkedToContact($event->getQueryBuilder(), (int) $tableConfig->getParameter('filterEntityId'));
        }
    }

    /**
     * @throws ORMException
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
     * @throws OptimisticLockException
     * @throws ORMException
     */
    public function saveLink(CustomItemXrefEntityEvent $event): void
    {
        if ($event->getXref() instanceof CustomItemXrefContact && !$this->entityManager->contains($event->getXref())) {
            $this->entityManager->persist($event->getXref());
            $this->entityManager->flush($event->getXref());
        }
    }

    public function createNewEventLogForLinkedContact(CustomItemXrefEntityEvent $event): void
    {
        if ($event->getXref() instanceof CustomItemXrefContact
            && CustomObject::TYPE_MASTER === $event->getXref()->getCustomItem()->getCustomObject()->getType()
        ) {
            $this->saveEventLog($event->getXref(), 'link');
        }
    }

    /**
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function deleteLink(CustomItemXrefEntityEvent $event): void
    {
        if ($event->getXref() instanceof CustomItemXrefContact && $this->entityManager->contains($event->getXref())) {
            $this->entityManager->remove($event->getXref());
            $this->entityManager->flush($event->getXref());
        }
    }

    /**
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function createNewEventLogForUnlinkedContact(CustomItemXrefEntityEvent $event): void
    {
        if ($event->getXref() instanceof CustomItemXrefContact
            && CustomObject::TYPE_MASTER === $event->getXref()->getCustomItem()->getCustomObject()->getType()
        ) {
            $this->saveEventLog($event->getXref(), 'unlink');
        }
    }

    /**
     * @throws ORMException
     * @throws OptimisticLockException
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
     * @param mixed[] $properties
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
     * @throws NoResultException        if the reference does not exist
     * @throws NonUniqueResultException
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
