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

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Doctrine\ORM\Query\Expr\Join;
use MauticPlugin\CustomObjectsBundle\CustomItemEvents;
use MauticPlugin\CustomObjectsBundle\Entity\CustomItem;
use MauticPlugin\CustomObjectsBundle\Entity\CustomItemXrefCustomItem;
use MauticPlugin\CustomObjectsBundle\Event\CustomItemListQueryEvent;
use MauticPlugin\CustomObjectsBundle\Event\CustomItemXrefEntityDiscoveryEvent;
use MauticPlugin\CustomObjectsBundle\Event\CustomItemXrefEntityEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use UnexpectedValueException;

class CustomItemXrefCustomItemSubscriber implements EventSubscriberInterface
{
    /**
     * @var EntityManager
     */
    private $entityManager;

    public function __construct(
        EntityManager $entityManager
    ) {
        $this->entityManager = $entityManager;
    }

    /**
     * @return mixed[]
     */
    public static function getSubscribedEvents(): array
    {
        return [
            CustomItemEvents::ON_CUSTOM_ITEM_LOOKUP_QUERY          => 'onLookupQuery',
            CustomItemEvents::ON_CUSTOM_ITEM_LIST_ORM_QUERY        => 'onListQuery',
            CustomItemEvents::ON_CUSTOM_ITEM_LINK_ENTITY_DISCOVERY => 'onEntityLinkDiscovery',
            CustomItemEvents::ON_CUSTOM_ITEM_LINK_ENTITY           => [
                ['saveLink', 1000],
                ['createNewEvenLogForLinkedCustomItem', 0],
            ],
            CustomItemEvents::ON_CUSTOM_ITEM_UNLINK_ENTITY         => [
                ['deleteLink', 1000],
                ['createNewEvenLogForUnlinkedCustomItem', 0],
            ],
        ];
    }

    public function onListQuery(CustomItemListQueryEvent $event): void
    {
        $tableConfig = $event->getTableConfig();
        if ('customItem' === $tableConfig->getParameter('filterEntityType') && $tableConfig->getParameter('filterEntityId')) {
            $queryBuilder = $event->getQueryBuilder();
            $queryBuilder->leftJoin(
                CustomItemXrefCustomItem::class,
                'CustomItemXrefCustomItem',
                Join::WITH,
                'CustomItem.id = CustomItemXrefCustomItem.customItemLower OR CustomItem.id = CustomItemXrefCustomItem.customItemHigher'
            );
            $queryBuilder->andWhere('CustomItem.id != :customItemId');
            $queryBuilder->andWhere($queryBuilder->expr()->orX(
                $queryBuilder->expr()->eq('CustomItemXrefCustomItem.customItemLower', ':customItemId'),
                $queryBuilder->expr()->eq('CustomItemXrefCustomItem.customItemHigher', ':customItemId')
            ));
            $queryBuilder->setParameter('customItemId', $tableConfig->getParameter('filterEntityId'));
        }
    }

    public function onLookupQuery(CustomItemListQueryEvent $event): void
    {
        $tableConfig = $event->getTableConfig();
        if ('customItem' === $tableConfig->getParameter('filterEntityType') && $tableConfig->getParameter('filterEntityId')) {
            $queryBuilder = $event->getQueryBuilder();
            $queryBuilder->leftJoin(
                CustomItemXrefCustomItem::class,
                'CustomItemXrefCustomItem',
                Join::WITH,
                'CustomItem.id = CustomItemXrefCustomItem.customItemLower OR CustomItem.id = CustomItemXrefCustomItem.customItemHigher'
            );
            $queryBuilder->andWhere('CustomItem.id != :customItemId');
            $queryBuilder->andWhere($queryBuilder->expr()->orX(
                $queryBuilder->expr()->andX(
                    $queryBuilder->expr()->neq('CustomItemXrefCustomItem.customItemLower', ':customItemId'),
                    $queryBuilder->expr()->neq('CustomItemXrefCustomItem.customItemHigher', ':customItemId')
                ),
                $queryBuilder->expr()->isNull('CustomItemXrefCustomItem.customItemLower'),
                $queryBuilder->expr()->isNull('CustomItemXrefCustomItem.customItemHigher')
            ));
            $queryBuilder->setParameter('customItemId', $tableConfig->getParameter('filterEntityId'));
        }
    }

    /**
     * @throws UnexpectedValueException
     * @throws ORMException
     */
    public function onEntityLinkDiscovery(CustomItemXrefEntityDiscoveryEvent $event): void
    {
        if ('customItem' === $event->getEntityType()) {
            try {
                $xRef = $this->getXrefEntity($event->getCustomItem()->getId(), $event->getEntityId());
            } catch (NoResultException $e) {
                /** @var CustomItem $customItemB */
                $customItemB = $this->entityManager->getReference(CustomItem::class, $event->getEntityId());
                $xRef        = new CustomItemXrefCustomItem($event->getCustomItem(), $customItemB);
            }

            $event->setXrefEntity($xRef);
            $event->stopPropagation();
        }
    }

    /**
     * Save the xref only if it isn't in the entity manager already as it means it was loaded from the database already.
     *
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function saveLink(CustomItemXrefEntityEvent $event): void
    {
        if ($event->getXref() instanceof CustomItemXrefCustomItem && !$this->entityManager->contains($event->getXref())) {
            $this->entityManager->persist($event->getXref());
            $this->entityManager->flush($event->getXref());
        }
    }

    public function createNewEvenLogForLinkedCustomItem(CustomItemXrefEntityEvent $event): void
    {
        if ($event->getXref() instanceof CustomItemXrefCustomItem) {
            // @todo
        }
    }

    /**
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function deleteLink(CustomItemXrefEntityEvent $event): void
    {
        if ($event->getXref() instanceof CustomItemXrefCustomItem && $this->entityManager->contains($event->getXref())) {
            $this->entityManager->remove($event->getXref());
            $this->entityManager->flush($event->getXref());
        }
    }

    public function createNewEvenLogForUnlinkedCustomItem(CustomItemXrefEntityEvent $event): void
    {
        if ($event->getXref() instanceof CustomItemXrefCustomItem) {
            // @todo
        }
    }

    /**
     * @throws NoResultException        if the reference does not exist
     * @throws NonUniqueResultException
     */
    public function getXrefEntity(int $customItemAId, int $customItemBId): CustomItemXrefCustomItem
    {
        $queryBuilder = $this->entityManager->createQueryBuilder();
        $queryBuilder->select('cixci');
        $queryBuilder->from(CustomItemXrefCustomItem::class, 'cixci');
        $queryBuilder->where('cixci.customItemLower = :customItemLower');
        $queryBuilder->andWhere('cixci.customItemHigher = :customItemHigher');
        $queryBuilder->setParameter('customItemLower', min($customItemAId, $customItemBId));
        $queryBuilder->setParameter('customItemHigher', max($customItemAId, $customItemBId));

        return $queryBuilder->getQuery()->getSingleResult();
    }
}
