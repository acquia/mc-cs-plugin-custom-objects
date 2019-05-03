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
use Doctrine\ORM\EntityManager;
use Mautic\CoreBundle\Helper\UserHelper;
use MauticPlugin\CustomObjectsBundle\Event\CustomItemXrefEntityDiscoveryEvent;
use Doctrine\ORM\NoResultException;
use MauticPlugin\CustomObjectsBundle\Entity\CustomItemXrefCustomItem;
use MauticPlugin\CustomObjectsBundle\Entity\CustomItem;
use UnexpectedValueException;
use MauticPlugin\CustomObjectsBundle\Event\CustomItemListQueryEvent;
use Doctrine\ORM\Query\Expr\Join;

class CustomItemXrefCustomItemSubscriber extends CommonSubscriber
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
            CustomItemEvents::ON_CUSTOM_ITEM_LIST_QUERY            => 'onListQuery',
            CustomItemEvents::ON_CUSTOM_ITEM_LINK_ENTITY_DISCOVERY => 'onEntityLinkDiscovery',
            CustomItemEvents::ON_CUSTOM_ITEM_LINK_ENTITY           => [
                ['saveLink', 1000],
                // ['createNewEvenLogForLinkedCustomItem', 0] // @todo
            ],
            CustomItemEvents::ON_CUSTOM_ITEM_UNLINK_ENTITY         => [
                ['deleteLink', 1000],
                // ['createNewEvenLogForUnlinkedCustomItem', 0] // @todo
            ],
        ];
    }

    /**
     * @param CustomItemListQueryEvent $event
     */
    public function onListQuery(CustomItemListQueryEvent $event): void
    {
        if ('customItem' === $event->getTableConfig()->getParameter('filterEntityType') && $event->getTableConfig()->getParameter('filterEntityId')) {
            $queryBuilder = $event->getQueryBuilder();
            $queryBuilder->leftJoin(
                CustomItemXrefCustomItem::class,
                'CustomItemXrefCustomItem',
                Join::WITH,
                'CustomItem.id = CustomItemXrefCustomItem.customItem OR CustomItem.id = CustomItemXrefCustomItem.parentCustomItem'
            );
            $queryBuilder->andWhere($queryBuilder->expr()->orX(
                $queryBuilder->expr()->eq('CustomItemXrefCustomItem.parentCustomItem', ':customItemId'),
                $queryBuilder->expr()->eq('CustomItemXrefCustomItem.customItem', ':customItemId')
            ));
            $queryBuilder->andWhere('CustomItem.id != :customItemId');
            $queryBuilder->setParameter('customItemId', $event->getTableConfig()->getParameter('filterEntityId'));
        }
    }

    /**
     * @param CustomItemXrefEntityDiscoveryEvent $event
     * 
     * @throws UnexpectedValueException
     */
    public function onEntityLinkDiscovery(CustomItemXrefEntityDiscoveryEvent $event): void
    {
        if ('customItem' === $event->getEntityType()) {
            if ($event->getCustomItem()->getId() === $event->getEntityId()) {
                throw new UnexpectedValueException("It is not possible to link identical custom item.");
            }

            try {
                $xRef = $this->getXrefEntity($event->getCustomItem()->getId(), $event->getEntityId());
            } catch (NoResultException $e) {
                /** @var CustomItem $parentCustomItem */
                $parentCustomItem = $this->entityManager->getReference(CustomItem::class, $event->getEntityId());
                $xRef             = new CustomItemXrefCustomItem($event->getCustomItem(), $parentCustomItem);
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
        if ($event->getXref() instanceof CustomItemXrefCustomItem && !$this->entityManager->contains($event->getXref())) {
            $this->entityManager->persist($event->getXref());
            $this->entityManager->flush($event->getXref());
        }
    }

    /**
     * @param CustomItemXrefEntityEvent $event
     */
    public function createNewEvenLogForLinkedCustomItem(CustomItemXrefEntityEvent $event): void
    {
        if ($event->getXref() instanceof CustomItemXrefCustomItem) {
            // @todo
        }
    }

    /**
     * @param CustomItemXrefEntityEvent $event
     */
    public function deleteLink(CustomItemXrefEntityEvent $event): void
    {
        if ($event->getXref() instanceof CustomItemXrefCustomItem && $this->entityManager->contains($event->getXref())) {
            $this->entityManager->remove($event->getXref());
            $this->entityManager->flush($event->getXref());
        }
    }

    /**
     * @param CustomItemXrefEntityEvent $event
     */
    public function createNewEvenLogForUnlinkedCustomItem(CustomItemXrefEntityEvent $event): void
    {
        if ($event->getXref() instanceof CustomItemXrefCustomItem) {
            // @todo
        }
    }

    /**
     * @param int $customItemId
     * @param int $parentCustomItemId
     *
     * @return CustomItemXrefCustomItem
     *
     * @throws NoResultException if the reference does not exist
     */
    public function getXrefEntity(int $customItemId, int $parentCustomItemId): CustomItemXrefCustomItem
    {
        $queryBuilder = $this->entityManager->createQueryBuilder();
        $queryBuilder->select('cixci');
        $queryBuilder->from(CustomItemXrefCustomItem::class, 'cixci');
        $queryBuilder->where('cixci.customItem = :customItemId');
        $queryBuilder->andWhere('cixci.parentCustomItem = :parentCustomItemId');
        $queryBuilder->setParameter('customItemId', $customItemId);
        $queryBuilder->setParameter('parentCustomItemId', $parentCustomItemId);

        return $queryBuilder->getQuery()->getSingleResult();
    }
}
