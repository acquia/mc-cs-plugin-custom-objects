<?php

declare(strict_types=1);

namespace MauticPlugin\CustomObjectsBundle\Repository;

use MauticPlugin\CustomObjectsBundle\Entity\CustomItemXrefCustomItem;

class CustomItemXrefCustomItemRepository extends CustomCommonRepository
{
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
