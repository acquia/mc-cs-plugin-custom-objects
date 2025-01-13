<?php

declare(strict_types=1);

namespace MauticPlugin\CustomObjectsBundle\Repository;

use Doctrine\ORM\QueryBuilder;
use Mautic\LeadBundle\Entity\Lead;
use MauticPlugin\CustomObjectsBundle\Entity\CustomItem;
use MauticPlugin\CustomObjectsBundle\Entity\CustomItemXrefContact;
use MauticPlugin\CustomObjectsBundle\Entity\CustomItemXrefCustomItem;
use MauticPlugin\CustomObjectsBundle\Entity\CustomObject;

class CustomItemRepository extends CustomCommonRepository
{
    use DbalQueryTrait;

    public function countItemsLinkedToContact(CustomObject $customObject, Lead $contact): int
    {
        $queryBuilder = $this->createQueryBuilder(CustomItem::TABLE_ALIAS);
        $queryBuilder->select($queryBuilder->expr()->countDistinct(CustomItem::TABLE_ALIAS.'.id'));
        $queryBuilder->where(CustomItem::TABLE_ALIAS.'.customObject = :customObjectId');
        $queryBuilder->setParameter('customObjectId', $customObject->getId());
        $this->includeItemsLinkedToContact($queryBuilder, (int) $contact->getId());

        return (int) $queryBuilder->getQuery()->getSingleScalarResult();
    }

    public function countItemsLinkedToAnotherItem(CustomObject $customObject, CustomItem $customItem): int
    {
        $queryBuilder = $this->createQueryBuilder(CustomItem::TABLE_ALIAS);
        $queryBuilder->select($queryBuilder->expr()->countDistinct(CustomItem::TABLE_ALIAS.'.id'));
        $queryBuilder->where(CustomItem::TABLE_ALIAS.'.customObject = :customObjectId');
        $queryBuilder->setParameter('customObjectId', $customObject->getId());
        $this->includeItemsLinkedToAnotherItem($queryBuilder, (int) $customItem->getId());

        return (int) $queryBuilder->getQuery()->getSingleScalarResult();
    }

    public function includeItemsLinkedToContact(QueryBuilder $queryBuilder, int $contactId): void
    {
        $queryBuilder->andWhere($queryBuilder->expr()->in(CustomItem::TABLE_ALIAS.'.id', $this->createContactReferencesBuilder()->getDQL()));
        $queryBuilder->setParameter('contactId', $contactId);
    }

    public function excludeItemsLinkedToContact(QueryBuilder $queryBuilder, int $contactId): void
    {
        $queryBuilder->andWhere($queryBuilder->expr()->notIn(CustomItem::TABLE_ALIAS.'.id', $this->createContactReferencesBuilder()->getDQL()));
        $queryBuilder->setParameter('contactId', $contactId);
    }

    public function includeItemsLinkedToAnotherItem(QueryBuilder $queryBuilder, int $customItemId): void
    {
        $exprBuilder = $queryBuilder->expr();

        $queryBuilder->andWhere($exprBuilder->orX(
            $exprBuilder->in(CustomItem::TABLE_ALIAS.'.id', $this->createLowerItemReferencesBuilder()->getDQL()),
            $exprBuilder->in(CustomItem::TABLE_ALIAS.'.id', $this->createHigherItemReferencesBuilder()->getDQL())
        ));
        $queryBuilder->setParameter('customItemId', $customItemId);
    }

    public function excludeItemsLinkedToAnotherItem(QueryBuilder $queryBuilder, int $customItemId): void
    {
        $exprBuilder = $queryBuilder->expr();

        $queryBuilder->andWhere($exprBuilder->notIn(CustomItem::TABLE_ALIAS.'.id', $this->createLowerItemReferencesBuilder()->getDQL()));
        $queryBuilder->andWhere($exprBuilder->notIn(CustomItem::TABLE_ALIAS.'.id', $this->createHigherItemReferencesBuilder()->getDQL()));
        $queryBuilder->setParameter('customItemId', $customItemId);
    }

    /**
     * Used by internal Mautic methods. Use the contstant difectly instead.
     */
    public function getTableAlias(): string
    {
        return CustomItem::TABLE_ALIAS;
    }

    /**
     * @throws \Doctrine\ORM\NonUniqueResultException
     * @throws \Doctrine\ORM\NoResultException
     */
    public function getItemCount(int $customObjectId): int
    {
        $queryBuilder = $this->getEntityManager()->createQueryBuilder();
        $queryBuilder->select('count('.CustomItem::TABLE_ALIAS.'.id)');
        $queryBuilder->from(CustomItem::class, CustomItem::TABLE_ALIAS);
        $queryBuilder->where(CustomItem::TABLE_ALIAS.'.customObject = :customObjectId');
        $queryBuilder->setParameter('customObjectId', $customObjectId);
        $queryBuilder->setMaxResults(1);

        return (int) $queryBuilder->getQuery()->getSingleScalarResult();
    }

    private function createContactReferencesBuilder(): QueryBuilder
    {
        $queryBuilder = $this->getEntityManager()->createQueryBuilder();
        $queryBuilder->select('IDENTITY(contactReference.customItem)');
        $queryBuilder->from(CustomItemXrefContact::class, 'contactReference');
        $queryBuilder->where('contactReference.contact = :contactId');

        return $queryBuilder;
    }

    private function createLowerItemReferencesBuilder(): QueryBuilder
    {
        $queryBuilder = $this->getEntityManager()->createQueryBuilder();
        $queryBuilder->select('IDENTITY(lower.customItemLower)');
        $queryBuilder->from(CustomItemXrefCustomItem::class, 'lower');
        $queryBuilder->where('lower.customItemHigher = :customItemId');

        return $queryBuilder;
    }

    private function createHigherItemReferencesBuilder(): QueryBuilder
    {
        $queryBuilder = $this->getEntityManager()->createQueryBuilder();
        $queryBuilder->select('IDENTITY(higher.customItemHigher)');
        $queryBuilder->from(CustomItemXrefCustomItem::class, 'higher');
        $queryBuilder->where('higher.customItemLower = :customItemId');

        return $queryBuilder;
    }

    /**
     * @return int|mixed|string
     */
    public function getCustomItemsRelatedToProvidedCustomObject(int $customObjectId, int $limit, int $offset)
    {
        return $this->createQueryBuilder('mautic_custom_item')
            ->select('mautic_custom_item')
            ->where('mautic_custom_item.customObject = :customObjectId')
            ->setParameter('customObjectId', $customObjectId)
            ->setMaxResults($limit)
            ->setFirstResult($offset)
            ->getQuery()
            ->getResult();
    }
}
