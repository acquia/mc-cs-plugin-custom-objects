<?php

declare(strict_types=1);

namespace MauticPlugin\CustomObjectsBundle\Repository;

use Mautic\CoreBundle\Entity\CommonRepository;
use MauticPlugin\CustomObjectsBundle\Entity\CustomItemXrefCustomItem;
use Doctrine\Persistence\ManagerRegistry;
/**
 * @extends CommonRepository<CustomItemXrefCustomItem>
 */
class CustomItemXrefCustomItemRepository extends CommonRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CustomItemXrefCustomItem::class);
    }
    public function deleteAllLinksForCustomItem(int $customItemId): void
    {
        $queryBuilder = $this->createQueryBuilder(CustomItemXrefCustomItem::TABLE_ALIAS);
        $queryBuilder->delete();
        $queryBuilder->where(CustomItemXrefCustomItem::TABLE_ALIAS.'.customItemLower = :customItemId');
        $queryBuilder->orWhere(CustomItemXrefCustomItem::TABLE_ALIAS.'.customItemHigher = :customItemId');
        $queryBuilder->setParameter('customItemId', $customItemId);
        $queryBuilder->getQuery()->execute();
    }
}
