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

            return;
        }
        
        if ($customFieldValue->getCustomField()->canHaveMultipleValues()) {
            if (is_array($customFieldValue->getValue())) {
                $values = $customFieldValue->getValue();
                foreach ($values as $value) {
                    $optionValueEntity = clone $customFieldValue;
                    $optionValueEntity->setValue($value);
                    $this->entityManager->persist($optionValueEntity);
                }
            }
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

        if ($customFieldValue->getCustomField()->canHaveMultipleValues()) {
            if (is_array($customFieldValue->getValue())) {
                $values = $customFieldValue->getValue();
                foreach ($values as $key => $value) {
                    $queryBuilder->set("{$fieldType->getTableAlias()}.value", ":value{$key}");
                    $queryBuilder->setParameter("value{$key}", $value);
                }
            }
        } else {
            $queryBuilder->set("{$fieldType->getTableAlias()}.value", ':value');
            $queryBuilder->setParameter('value', $customFieldValue->getValue());
        }

        $queryBuilder->where("{$fieldType->getTableAlias()}.customField = :customFieldId");
        $queryBuilder->andWhere("{$fieldType->getTableAlias()}.customItem = :customItemId");
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
        // Use new collection so we could set keys as well.
        $customFieldValues = new ArrayCollection();

        $customFields->map(function (CustomField $customField) use ($customItem, $customFieldValues) {
            $customFieldValue = $customField->getTypeObject()->createValueEntity($customField, $customItem);
            $customFieldValue->setValue($customField->getDefaultValue());
            $customFieldValues->set($customField->getId(), $customFieldValue);
        });

        $valueRows->map(function (array $row) use ($customFieldValues) {
            /** @var CustomFieldValueInterface */
            $customFieldValue = $customFieldValues->get((int) $row['custom_field_id']);

            $customFieldValue->updateThisEntityManually();

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
