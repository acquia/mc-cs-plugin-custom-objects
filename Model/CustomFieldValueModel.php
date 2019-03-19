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
use MauticPlugin\CustomObjectsBundle\Entity\CustomFieldValueOption;

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
     *
     * @return Collection
     */
    public function createValuesForItem(CustomItem $customItem): Collection
    {
        $customFields  = $customItem->getCustomObject()->getPublishedFields();
        $queries       = $this->buildQueriesForUnion($customItem, $customFields);
        $valueRows     = $this->fetchValues($queries);
        $valueEntities = $this->createValueEntities($customFields, $customItem);

        return $this->setValuesFromDatabase($valueRows, $valueEntities);
    }

    /**
     * If the entities were created manually, not fetched by Entity Manager
     * then we have to update them manually without help of EntityManager.
     *
     * @param CustomFieldValueInterface $customFieldValue
     */
    public function save(CustomFieldValueInterface $customFieldValue): void
    {
        if ($customFieldValue->getCustomField()->canHaveMultipleValues()) {
            $this->deleteOptionsForField($customFieldValue);
            foreach ($customFieldValue->getValue() as $optionKey) {
                $optionValue = clone $customFieldValue;
                $optionValue->setValue($optionKey);
                $this->entityManager->persist($optionValue);
            }

            return;
        }
        
        if ($customFieldValue->getCustomItem()->getId()){
            $this->entityManager->merge($customFieldValue);
        } else {
            $this->entityManager->persist($customFieldValue);
        }
    }

    /**
     * @param CustomFieldValueInterface $customFieldValue
     * 
     * @return int Number of deleted rows
     */
    private function deleteOptionsForField(CustomFieldValueInterface $customFieldValue): int
    {
        $entityClass = CustomFieldValueOption::class;
        $dql         = "
            delete from {$entityClass} cfvo  
            where cfvo.customField = {$customFieldValue->getCustomField()->getId()}
            and cfvo.customItem = {$customFieldValue->getCustomItem()->getId()}
        ";

        $query       = $this->entityManager->createQuery($dql);
        $deletedRows = $query->execute();

        return $deletedRows;
    }

    /**
     * @param Collection $valueRows
     * @param Collection $customFields
     * @param CustomItem $customItem
     *
     * @return Collection
     */
    private function createValueEntities(Collection $customFields, CustomItem $customItem): Collection
    {
        return $customFields->map(function (CustomField $customField) use ($customItem) {
            $customFieldValue = $customField->getTypeObject()->createValueEntity($customField, $customItem);
            $customFieldValue->setValue($customField->getDefaultValue());
            $customItem->setCustomFieldValue($customFieldValue);

            return $customFieldValue;
        });
    }

    /**
     * @param Collection $valueRows
     * @param Collection $customFields
     *
     * @return Collection
     */
    private function setValuesFromDatabase(Collection $valueRows, Collection $customFieldValues): Collection
    {
        $valueRows->map(function (array $row) use ($customFieldValues) {
            /** @var CustomFieldValueInterface */
            $customFieldValue = $customFieldValues->get((int) $row['custom_field_id']);

            if ($customFieldValue->getCustomField()->canHaveMultipleValues()) {
                $customFieldValue->addValue($row['value']);
            } else {
                $customFieldValue->setValue($row['value']);
            }
        });

        return $customFieldValues;
    }

    /**
     * @param Collection $queries
     *
     * @return ArrayCollection
     */
    private function fetchValues(Collection $queries): ArrayCollection
    {
        // No need to query for values in case there are no queries
        if (0 === $queries->count()) {
            return new ArrayCollection();
        }

        $statement = $this->entityManager->getConnection()->prepare(implode(' UNION ', $queries->toArray()));

        $statement->execute();

        return new ArrayCollection($statement->fetchAll());
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
