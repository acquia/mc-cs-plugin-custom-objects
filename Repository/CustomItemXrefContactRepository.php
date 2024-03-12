<?php

declare(strict_types=1);

namespace MauticPlugin\CustomObjectsBundle\Repository;

use Mautic\CoreBundle\Entity\CommonRepository;
use Mautic\LeadBundle\Entity\Lead;
use MauticPlugin\CustomObjectsBundle\DTO\TableConfig;
use MauticPlugin\CustomObjectsBundle\Entity\CustomItem;
use MauticPlugin\CustomObjectsBundle\Entity\CustomItemXrefContact;
use MauticPlugin\CustomObjectsBundle\Entity\CustomObject;

class CustomItemXrefContactRepository extends CommonRepository
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

    public function mergeLead(Lead $victor, Lead $loser): void
    {
        // Move all custom item references to the victor lead, but only if the victor doesn't already have
        // a reference to the custom item
        $existingAlias = CustomItemXrefContact::TABLE_ALIAS.'_Check';

        $this->createQueryBuilder(CustomItemXrefContact::TABLE_ALIAS)
            ->update()
            ->set(CustomItemXrefContact::TABLE_ALIAS.'.contact', ':victor')
            ->where(CustomItemXrefContact::TABLE_ALIAS.'.contact = :loser')
            ->andWhere(
                $this->createQueryBuilder(CustomItemXrefContact::TABLE_ALIAS)
                    ->expr()
                    ->notIn(
                        CustomItemXrefContact::TABLE_ALIAS.'.customItem',
                        $this->createQueryBuilder($existingAlias)
                            ->select('IDENTITY('.$existingAlias.'.customItem)')
                            ->where($existingAlias.'.contact = :victor')
                            ->getDQL()
                    )
            )
            ->setParameter('victor', $victor->getId())
            ->setParameter('loser', $loser->getId())
            ->getQuery()
            ->execute();

    }
}
