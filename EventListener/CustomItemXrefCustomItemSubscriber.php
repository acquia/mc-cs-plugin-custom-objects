<?php

declare(strict_types=1);

namespace MauticPlugin\CustomObjectsBundle\EventListener;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use MauticPlugin\CustomObjectsBundle\CustomItemEvents;
use MauticPlugin\CustomObjectsBundle\Entity\CustomItem;
use MauticPlugin\CustomObjectsBundle\Entity\CustomItemXrefCustomItem;
use MauticPlugin\CustomObjectsBundle\Event\CustomItemListQueryEvent;
use MauticPlugin\CustomObjectsBundle\Event\CustomItemXrefEntityDiscoveryEvent;
use MauticPlugin\CustomObjectsBundle\Event\CustomItemXrefEntityEvent;
use MauticPlugin\CustomObjectsBundle\Repository\CustomItemRepository;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use UnexpectedValueException;

class CustomItemXrefCustomItemSubscriber implements EventSubscriberInterface
{
    /**
     * @var EntityManager
     */
    private $entityManager;

    /**
     * @var CustomItemRepository
     */
    private $customItemRepository;

    public function __construct(EntityManager $entityManager, CustomItemRepository $customItemRepository)
    {
        $this->entityManager        = $entityManager;
        $this->customItemRepository = $customItemRepository;
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
            $queryBuilder   = $event->getQueryBuilder();
            $filterEntityId = (int) $tableConfig->getParameter('filterEntityId');

            if ($tableConfig->getParameter('lookup')) {
                $this->customItemRepository->excludeItemsLinkedToAnotherItem($queryBuilder, $filterEntityId);
            } else {
                $this->customItemRepository->includeItemsLinkedToAnotherItem($queryBuilder, $filterEntityId);
            }
        }
    }

    public function onLookupQuery(CustomItemListQueryEvent $event): void
    {
        $tableConfig = $event->getTableConfig();
        if ('customItem' === $tableConfig->getParameter('filterEntityType') && $tableConfig->getParameter('filterEntityId')) {
            $this->customItemRepository->excludeItemsLinkedToAnotherItem($event->getQueryBuilder(), (int) $tableConfig->getParameter('filterEntityId'));
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
