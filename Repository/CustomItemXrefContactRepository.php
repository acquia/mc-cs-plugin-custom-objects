<?php

declare(strict_types=1);

namespace MauticPlugin\CustomObjectsBundle\Repository;

use Mautic\LeadBundle\Entity\Lead;
use MauticPlugin\CustomObjectsBundle\DTO\TableConfig;
use MauticPlugin\CustomObjectsBundle\Entity\CustomItem;
use MauticPlugin\CustomObjectsBundle\Entity\CustomItemXrefContact;
use MauticPlugin\CustomObjectsBundle\Entity\CustomObject;

class CustomItemXrefContactRepository extends CustomCommonRepository
{
    /**
     * @return int[]
     */
    public function getCustomObjectsRelatedToContact(Lead $contact, TableConfig $tableConfig): array
    {
        $q = $this->createQueryBuilder(CustomItemXrefContact::TABLE_ALIAS);
        $q = $tableConfig->configureOrmQueryBuilder($q);
        $q->select(CustomObject::TABLE_ALIAS.'.id');
        $q->addSelect(CustomObject::TABLE_ALIAS.'.alias');
        $q->innerJoin(CustomItemXrefContact::TABLE_ALIAS.'.customItem', CustomItem::TABLE_ALIAS);
        $q->innerJoin(CustomItem::TABLE_ALIAS.'.customObject', CustomObject::TABLE_ALIAS);
        $q->where(CustomItemXrefContact::TABLE_ALIAS.'.contact = :contactId');
        $q->groupBy(CustomObject::TABLE_ALIAS.'.id');
        $q->andWhere(CustomObject::TABLE_ALIAS.'.isPublished = 1');
        $q->setParameter('contactId', $contact->getId());

        return $q->getQuery()->getResult();
    }

    public function deleteAllLinksForCustomItem(int $customItemId): void
    {
        $queryBuilder = $this->createQueryBuilder(CustomItemXrefContact::TABLE_ALIAS);
        $queryBuilder->delete();
        $queryBuilder->where(CustomItemXrefContact::TABLE_ALIAS.'.customItem = :customItemId');
        $queryBuilder->setParameter('customItemId', $customItemId);
        $queryBuilder->getQuery()->execute();
    }

    /**
     * Used by internal Mautic methods. Use the constant directly instead.
     */
    public function getTableAlias(): string
    {
        return CustomItemXrefContact::TABLE_ALIAS;
    }

    /**
     * @return int|mixed|string
     */
    public function getContactIdsLinkedToCustomItem(int $customItemId, int $limit, int $offset)
    {
        return $this->createQueryBuilder(CustomItemXrefContact::TABLE_ALIAS)
            ->select('IDENTITY('.CustomItemXrefContact::TABLE_ALIAS.'.contact) AS contact_id')
            ->where(CustomItemXrefContact::TABLE_ALIAS.'.customItem = :customItemId')
            ->setParameter('customItemId', $customItemId)
            ->setMaxResults($limit)
            ->setFirstResult($offset)
            ->getQuery()
            ->getResult();
    }
}
