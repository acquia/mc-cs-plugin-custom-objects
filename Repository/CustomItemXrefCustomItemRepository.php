<?php

declare(strict_types=1);

namespace MauticPlugin\CustomObjectsBundle\Repository;

use Doctrine\Persistence\ManagerRegistry;
use Mautic\CoreBundle\Entity\CommonRepository;
use MauticPlugin\CustomObjectsBundle\Entity\CustomItemXrefCustomItem;

class CustomItemXrefCustomItemRepository extends CommonRepository
{
    public function __construct(ManagerRegistry $registry, string $entityFQCN = null)
    {
        $entityFQCN = $entityFQCN ?? preg_replace('/(.*)\\\\Repository(.*)Repository?/', '$1\Entity$2', get_class($this));
        parent::__construct($registry, $entityFQCN);
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
