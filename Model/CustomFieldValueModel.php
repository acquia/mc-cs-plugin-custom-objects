<?php

declare(strict_types=1);

/*
 * @copyright   2019 Mautic, Inc. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.com
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\CustomObjectsBundle\Model;

use MauticPlugin\CustomObjectsBundle\Entity\CustomFieldValueInterface;
use Doctrine\ORM\EntityManager;
use MauticPlugin\CustomObjectsBundle\Entity\CustomItem;
use MauticPlugin\CustomObjectsBundle\Entity\CustomField;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

class CustomFieldValueModel
{
    /**
     * @var EntityManager
     */
    private $entityManager;

    /**
     * @param EntityManager $entityManager
     */
    public function __construct(
        EntityManager $entityManager
    ) {
        $this->entityManager = $entityManager;
    }

    /**
     * The values are joined from several tables. Each value type can have own table.
     *
     * @param CustomItem $customItem
     * @param Collection $customFields
     *
     * @return Collection
     */
    public function getValuesForItem(CustomItem $customItem, Collection $customFields): Collection
    {
        return $this->createValueObjects(
            $this->fetchValues(
                $this->buildQueriesForUnion($customItem, $customFields)
            ),
            $customFields,
            $customItem
        );
    }

    /**
     * If the entities were created manually, not fetched by Entity Manager
     * then we have to update them manually without help of EntityManager.
     *
     * @param CustomFieldValueInterface $customFieldValue
     */
    public function save(CustomFieldValueInterface $customFieldValue): void
    {
        if ($customFieldValue->shouldBeUpdatedManually()) {
            $this->updateManually($customFieldValue);
            $this->entityManager->detach($customFieldValue);
        } else {
            $this->entityManager->persist($customFieldValue);
        }
    }

    /**
     * @param CustomFieldValueInterface $customFieldValue
     */
    private function updateManually(CustomFieldValueInterface $customFieldValue): void
    {
        $fieldType    = $customFieldValue->getCustomField()->getTypeObject();
        $queryBuilder = $this->entityManager->createQueryBuilder();
        $queryBuilder->update($fieldType->getEntityClass(), $fieldType->getTableAlias());
        $queryBuilder->set("{$fieldType->getTableAlias()}.value", ':value');
        $queryBuilder->where("{$fieldType->getTableAlias()}.customField = :customFieldId");
        $queryBuilder->andWhere("{$fieldType->getTableAlias()}.customItem = :customItemId");
        $queryBuilder->setParameter('value', $customFieldValue->getValue());
        $queryBuilder->setParameter('customFieldId', (int) $customFieldValue->getCustomField()->getId());
        $queryBuilder->setParameter('customItemId', (int) $customFieldValue->getCustomItem()->getId());
        $queryBuilder->getQuery()->execute();
    }

    /**
     * @param Collection $valueRows
     * @param Collection $customFields
     * @param CustomItem $customItem
     *
     * @return Collection
     */
    private function createValueObjects(Collection $valueRows, Collection $customFields, CustomItem $customItem): Collection
    {
        return $customFields->map(function (CustomField $customField) use ($customItem, $valueRows) {
            $entityClass      = $customField->getTypeObject()->getEntityClass();
            $customFieldValue = new $entityClass($customField, $customItem);
            $valueRow         = $valueRows->get($customField->getId());

            if (is_array($valueRow) && array_key_exists('value', $valueRow)) {
                $value = $valueRow['value'];
                $customFieldValue->updateThisEntityManually();
            } else {
                $value = $customField->getDefaultValue();
            }

            $customFieldValue->setValue($value);

            return $customFieldValue;
        });
    }

    /**
     * @param Collection $queries
     *
     * @return ArrayCollection
     */
    private function fetchValues(Collection $queries): ArrayCollection
    {
        $rows = new ArrayCollection();

        // No need to query for values in case there are no queries
        if (0 === $queries->count()) {
            return $rows;
        }

        $statement = $this->entityManager->getConnection()->prepare(implode(' UNION ', $queries->toArray()));

        $statement->execute();

        $rowsRaw = $statement->fetchAll();

        foreach ($rowsRaw as $row) {
            $rows->set((int) $row['custom_field_id'], $row);
        }

        return $rows;
    }

    /**
     * @param CustomItem $customItem
     * @param Collection $customFields
     *
     * @return Collection
     */
    private function buildQueriesForUnion(CustomItem $customItem, Collection $customFields): Collection
    {
        // No need to build queries for new CustomItem entity
        if ($customItem->isNew()) {
            return new ArrayCollection();
        }

        return $customFields->map(function (CustomField $customField) use ($customItem) {
            $type         = $customField->getTypeObject();
            $alias        = $type->getTableAlias();
            $queryBuilder = $this->entityManager->getConnection()->createQueryBuilder();
            $queryBuilder->select("{$alias}.custom_field_id, {$alias}.value, '{$type->getKey()}' AS type");
            $queryBuilder->from($type->getTableName(), $alias);
            $queryBuilder->where("{$alias}.custom_item_id = {$customItem->getId()}");
            $queryBuilder->andWhere("{$alias}.custom_field_id = {$customField->getId()}");

            return $queryBuilder->getSQL();
        });
    }
}
