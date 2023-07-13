<?php

declare(strict_types=1);

namespace MauticPlugin\CustomObjectsBundle\Model;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManager;
use MauticPlugin\CustomObjectsBundle\DTO\CustomItemFieldListData;
use MauticPlugin\CustomObjectsBundle\Entity\CustomField;
use MauticPlugin\CustomObjectsBundle\Entity\CustomFieldValueInterface;
use MauticPlugin\CustomObjectsBundle\Entity\CustomFieldValueOption;
use MauticPlugin\CustomObjectsBundle\Entity\CustomItem;
use MauticPlugin\CustomObjectsBundle\Exception\InvalidValueException;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class CustomFieldValueModel
{
    /**
     * @var EntityManager
     */
    private $entityManager;

    /**
     * @var ValidatorInterface
     */
    private $validator;

    public function __construct(
        EntityManager $entityManager,
        ValidatorInterface $validator
    ) {
        $this->entityManager = $entityManager;
        $this->validator     = $validator;
    }

    /**
     * The values are joined from several tables. Each value type can have own table.
     */
    public function createValuesForItem(CustomItem $customItem): void
    {
        $customFields  = $customItem->getCustomObject()->getPublishedFields();
        $queries       = $this->buildQueriesForUnion($customItem, $customFields);
        $valueRows     = $this->fetchValues($queries);

        $this->createValueEntities($customFields, $customItem);
        $this->setValuesFromDatabase($valueRows, $customItem);
    }

    /**
     * If the entities were created manually, not fetched by Entity Manager
     * then we have to merge them to the entity manager and flush.
     * New entities are just persisted. Call flush after.
     */
    public function save(CustomFieldValueInterface $customFieldValue, bool $dryRun = false): void
    {
        $customFieldValue->setCustomItem($this->entityManager->getReference(CustomItem::class, $customFieldValue->getCustomItem()->getId()));
        if ($customFieldValue->getCustomField()->canHaveMultipleValues()) {
            $this->deleteOptionsForField($customFieldValue);
            $options = $customFieldValue->getValue();
            if (empty($options)) {
                $options = [];
            } elseif (is_string($options)) {
                if (false !== mb_strpos($options, ',')) {
                    $options = explode(',', $options);
                } else {
                    $options = [$options];
                }
            }
            foreach ($options as $optionKey) {
                $optionKey   = is_string($optionKey) ? trim($optionKey) : $optionKey;
                $optionValue = clone $customFieldValue;
                $optionValue->setValue($optionKey);

                $errors = $this->validator->validate($optionValue);

                if ($errors->count() > 0) {
                    $exception = new InvalidValueException($errors->get(0)->getMessage());
                    $exception->setCustomField($customFieldValue->getCustomField());

                    throw $exception;
                }

                if (!$dryRun) {
                    $this->entityManager->persist($optionValue);
                }
            }

            return;
        }

        $errors = $this->validator->validate($customFieldValue);

        if ($errors->count() > 0) {
            $exception = new InvalidValueException($errors->get(0)->getMessage());
            $exception->setCustomField($customFieldValue->getCustomField());

            throw $exception;
        }

        if (!$dryRun) {
            if ($customFieldValue->getCustomItem()->getId()) {
                $customFieldValue = $this->entityManager->merge($customFieldValue);
                $this->entityManager->flush($customFieldValue);
            } else {
                $this->entityManager->persist($customFieldValue);
            }
        }
    }

    /**
     * @param CustomItem[] $customItems
     */
    public function getItemsListData(Collection $customFields, array $customItems): ?CustomItemFieldListData
    {
        if (0 === count($customFields) || 0 === count($customItems)) {
            return null;
        }

        $columns = $customFields->map(function (CustomField $customField) {
            return $customField->getLabel();
        });
        $data = $this->buildItemsListData($customFields->toArray(), $customItems);

        return new CustomItemFieldListData($columns->toArray(), $data);
    }

    /**
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

        $query = $this->entityManager->createQuery($dql);

        return $query->execute();
    }

    /**
     * Creates custom field value entities and add them to the CustomItem entity.
     */
    private function createValueEntities(Collection $customFields, CustomItem $customItem): void
    {
        $customFields->map(function (CustomField $customField) use ($customItem): void {
            $customItem->addCustomFieldValue(
                $customField->getTypeObject()->createValueEntity(
                    $customField,
                    $customItem
                )
            );
        });
    }

    private function setValuesFromDatabase(Collection $valueRows, CustomItem $customItem): void
    {
        $customFieldValues = $customItem->getCustomFieldValues();

        $valueRows->map(function (array $row) use ($customFieldValues): void {
            /** @var CustomFieldValueInterface */
            $customFieldValue = $customFieldValues->get((int) $row['custom_field_id']);
            $this->setValueToField($customFieldValue, $row['value']);
        });
    }

    /**
     * @param mixed $value
     */
    private function setValueToField(CustomFieldValueInterface $customFieldValue, $value): void
    {
        if ($customFieldValue->getCustomField()->canHaveMultipleValues()) {
            $customFieldValue->addValue($value);
        } else {
            $customFieldValue->setValue($value);
        }
    }

    private function fetchValues(Collection $queries): ArrayCollection
    {
        // No need to query for values in case there are no queries
        if (0 === $queries->count()) {
            return new ArrayCollection();
        }

        $query     = implode(' UNION ALL ', $queries->toArray());
        $statement = $this->entityManager->getConnection()->prepare($query);

        return new ArrayCollection($statement->execute()->fetchAll());
    }

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
            $queryBuilder->select("{$alias}.custom_field_id, {$alias}.custom_item_id, {$alias}.value, '{$type->getKey()}' AS type");
            $queryBuilder->from($type->getPrefixedTableName(), $alias);
            $queryBuilder->where("{$alias}.custom_item_id = {$customItem->getId()}");
            $queryBuilder->andWhere("{$alias}.custom_field_id = {$customField->getId()}");

            return $queryBuilder->getSQL();
        });
    }

    /**
     * @param CustomField[] $customFields
     * @param CustomItem[]  $customItems
     */
    private function buildItemsListData(array $customFields, array $customItems): array
    {
        $result = $this->fetchItemsListData($customFields, $customItems);
        $result = $this->transformItemsListDataResult($result);

        return array_reduce($customItems, function (array $data, CustomItem $customItem) use ($customFields, $result) {
            $fields = [];

            foreach ($customFields as $customField) {
                $customFieldValue = $customField->getTypeObject()->createValueEntity($customField, $customItem);

                foreach ($result[$customItem->getId()][$customField->getId()] ?? [] as $value) {
                    $this->setValueToField($customFieldValue, $value);
                }

                $fields[$customField->getId()] = $customFieldValue;
            }

            $data[$customItem->getId()] = $fields;

            return $data;
        }, []);
    }

    /**
     * @param CustomField[] $customFields
     * @param CustomItem[]  $customItems
     */
    private function fetchItemsListData(array $customFields, array $customItems): array
    {
        // create a map [tableName] = [fieldId, fieldId, ...] for creating queries
        $tableToCustomFieldIds = array_reduce($customFields, function (array $tables, CustomField $customField) {
            $tableName = $customField->getTypeObject()->getPrefixedTableName();

            if (!isset($tables[$tableName])) {
                $tables[$tableName] = [];
            }

            $tables[$tableName][] = $customField->getId();

            return $tables;
        }, []);

        // create queries for fetching field values via union
        $queries = array_map(function (string $table) {
            $queryBuilder = $this->entityManager->getConnection()->createQueryBuilder();
            $queryBuilder->select('custom_item_id, custom_field_id, value');
            $queryBuilder->from($table);
            $queryBuilder->where('custom_item_id IN (:itemIds)');
            $queryBuilder->andWhere("custom_field_id IN (:{$table})");

            return $queryBuilder->getSQL();
        }, array_keys($tableToCustomFieldIds));

        // extract item IDs
        $itemIds = array_map(function (CustomItem $customItem) {
            return $customItem->getId();
        }, $customItems);

        $params = ['itemIds' => $itemIds];
        $types  = ['itemIds' => Connection::PARAM_INT_ARRAY];

        foreach ($tableToCustomFieldIds as $table => $customFieldIds) {
            $params[$table] = $customFieldIds;
            $types[$table]  = Connection::PARAM_INT_ARRAY;
        }

        return $this->entityManager->getConnection()->fetchAll(implode(' UNION ALL ', $queries), $params, $types);
    }

    /**
     * Transforms DB result into the following structure:
     *     $result[itemId][fieldId] = [value1, value2, ...].
     */
    private function transformItemsListDataResult(array $result): array
    {
        return array_reduce($result, function (array $result, array $row) {
            $itemId = $row['custom_item_id'];
            $fieldId = $row['custom_field_id'];

            if (!isset($result[$itemId])) {
                $result[$itemId] = [];
            }

            if (!isset($result[$itemId][$fieldId])) {
                $result[$itemId][$fieldId] = [];
            }

            $result[$itemId][$fieldId][] = $row['value'];

            return $result;
        }, []);
    }
}
