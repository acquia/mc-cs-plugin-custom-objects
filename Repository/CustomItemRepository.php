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

namespace MauticPlugin\CustomObjectsBundle\Repository;

use MauticPlugin\CustomObjectsBundle\Entity\CustomObject;
use Mautic\LeadBundle\Entity\Lead;
use MauticPlugin\CustomObjectsBundle\Entity\CustomField;
use MauticPlugin\CustomObjectsBundle\Exception\NotFoundException;
use MauticPlugin\CustomObjectsBundle\Entity\CustomItem;
use Doctrine\ORM\Query\Expr\Join;
use MauticPlugin\CustomObjectsBundle\Entity\CustomItemXrefCustomItem;

class CustomItemRepository extends AbstractTableRepository
{
    use DbalQueryTrait;

    public const TABLE_ALIAS = 'CustomItem';

    /**
     * @param Lead         $contact
     * @param CustomObject $customObject
     *
     * @return int
     */
    public function countItemsLinkedToContact(CustomObject $customObject, Lead $contact): int
    {
        $queryBuilder = $this->createQueryBuilder('ci', 'ci.id');
        $queryBuilder->select($queryBuilder->expr()->countDistinct('ci.id'));
        $queryBuilder->innerJoin('ci.contactReferences', 'cixctct');
        $queryBuilder->where('ci.customObject = :customObjectId');
        $queryBuilder->andWhere('cixctct.contact = :contactId');
        $queryBuilder->setParameter('customObjectId', $customObject->getId());
        $queryBuilder->setParameter('contactId', $contact->getId());

        return (int) $queryBuilder->getQuery()->getSingleScalarResult();
    }

    /**
     * @param CustomItem   $customItem
     * @param CustomObject $customObject
     *
     * @return int
     */
    public function countItemsLinkedToAnotherItem(CustomObject $customObject, CustomItem $customItem): int
    {
        $queryBuilder = $this->createQueryBuilder('ci', 'ci.id');
        $queryBuilder->select($queryBuilder->expr()->countDistinct('ci.id'));
        $queryBuilder->innerJoin(CustomItemXrefCustomItem::class, 'cixci', Join::WITH, 'ci.id = cixci.customItemLower OR ci.id = cixci.customItemHigher');
        $queryBuilder->where('ci.customObject = :customObjectId');
        $queryBuilder->andWhere('ci.id != :customItemId');
        $queryBuilder->andWhere($queryBuilder->expr()->orX(
            $queryBuilder->expr()->eq('cixci.customItemLower', ':customItemId'),
            $queryBuilder->expr()->eq('cixci.customItemHigher', ':customItemId')
        ));
        $queryBuilder->setParameter('customObjectId', $customObject->getId());
        $queryBuilder->setParameter('customItemId', $customItem->getId());

        return (int) $queryBuilder->getQuery()->getSingleScalarResult();
    }

    /**
     * @param CustomField $customField
     * @param Lead        $contact
     * @param string      $expr
     * @param mixed       $value
     *
     * @return int
     *
     * @throws NotFoundException
     */
    public function findItemIdForValue(CustomField $customField, Lead $contact, string $expr, $value): int
    {
        $fieldType    = $customField->getTypeObject();
        $queryBuilder = $this->_em->getConnection()->createQueryBuilder();
        $queryBuilder->select('ci.id');
        $queryBuilder->from(MAUTIC_TABLE_PREFIX.'custom_item', 'ci');
        $queryBuilder->innerJoin('ci', MAUTIC_TABLE_PREFIX.'custom_item_xref_contact', 'cixcont', 'cixcont.custom_item_id = ci.id');
        $queryBuilder->innerJoin('ci', $fieldType->getTableName(), $fieldType->getTableAlias(), "{$fieldType->getTableAlias()}.custom_item_id = ci.id");
        $queryBuilder->where('cixcont.contact_id = :contactId');
        $queryBuilder->setParameter('contactId', $contact->getId());
        $queryBuilder->andWhere("{$fieldType->getTableAlias()}.custom_field_id = :customFieldId");
        $queryBuilder->setParameter('customFieldId', $customField->getId());
        $queryBuilder->andWhere($queryBuilder->expr()->{$expr}("{$fieldType->getTableAlias()}.value", ':value'));
        $queryBuilder->setParameter('value', $value);

        $result = $this->executeSelect($queryBuilder)->fetchColumn();

        if (false === $result) {
            $stringValue = print_r($value, true);
            $msg         = "Custom Item for contact {$contact->getId()}, custom field {$customField->getId()} and value {$expr} {$stringValue} was not found.";

            throw new NotFoundException($msg);
        }

        return (int) $result;
    }
}
