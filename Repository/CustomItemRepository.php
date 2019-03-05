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

class CustomItemRepository extends AbstractTableRepository
{
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
        $result = $queryBuilder->getQuery()->getSingleScalarResult();

        return (int) $result;
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
        $queryBuilder->andWhere($queryBuilder->expr()->{$expr}("{$fieldType->getTableAlias()}.value", $value));

        $result = $queryBuilder->execute()->fetchColumn();

        if (false === $result) {
            $stringValue = print_r($value, true);
            $msg         = "Custom Item for contact {$contact->getId()}, custom field {$customField->getId()} and value {$expr} {$stringValue} was not found.";

            throw new NotFoundException($msg);
        }

        return (int) $result;
    }
}
