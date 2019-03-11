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
use MauticPlugin\CustomObjectsBundle\Entity\CustomObject;

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
     * @return ArrayCollection
     */
    public function getValuesForItem(CustomItem $customItem, Collection $customFields): ArrayCollection
    {
        if (!$customItem->getId()) {
            return new ArrayCollection();
        }

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
    public function updateManually(CustomFieldValueInterface $customFieldValue): void
    {
        $fieldType    = $customFieldValue->getCustomField()->getTypeObject();
        $queryBuilder = $this->entityManager->createQueryBuilder();
        $queryBuilder->update($fieldType->getEntityClass(), $fieldType->getTableAlias())
            ->set("{$fieldType->getTableAlias()}.value", ':value')
            ->where("{$fieldType->getTableAlias()}.customField = :customFieldId")
            ->andWhere("{$fieldType->getTableAlias()}.customItem = :customItemId")
            ->setParameter('value', $customFieldValue->getValue())
            ->setParameter('customFieldId', (int) $customFieldValue->getCustomField()->getId())
            ->setParameter('customItemId', (int) $customFieldValue->getCustomItem()->getId());
        $query = $queryBuilder->getQuery();
        $query->execute();
    }

    /**
     * @param array      $valueRows
     * @param Collection $customFields
     * @param CustomItem $customItem
     * 
     * @return Collection
     */
    private function createValueObjects(array $valueRows, Collection $customFields, CustomItem $customItem): Collection
    {
        return $customFields->map(function(CustomField $customField) use ($customItem, $valueRows) {
            $entityClass      = $customField->getTypeObject()->getEntityClass();
            $customFieldValue = new $entityClass($customField, $customItem);

            if (isset($valueRows[$customField->getId()])) {
                $value = $valueRows[$customField->getId()]['value'];
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
     * @return array
     */
    private function fetchValues(Collection $queries): array
    {
        $statement = $this->entityManager->getConnection()->prepare(implode(' UNION ', $queries->toArray()));

        $statement->execute();

        $rowsRaw = $statement->fetchAll();
        $rows    = [];

        foreach ($rowsRaw as $row) {
            $rows[$row['custom_field_id']] = $row;
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
        return $customFields->map(function(CustomField $customField) use ($customItem) {
            $type         = $customField->getTypeObject();
            $queryBuilder = $this->entityManager->getConnection()->createQueryBuilder();
            $queryBuilder->select("{$type->getTableAlias()}.custom_field_id, {$type->getTableAlias()}.value, '{$type->getKey()}' AS type");
            $queryBuilder->from($type->getTableName(), $type->getTableAlias());
            $queryBuilder->where("{$type->getTableAlias()}.custom_item_id = {$customItem->getId()}");
            $queryBuilder->andWhere("{$type->getTableAlias()}.custom_field_id = {$customField->getId()}");

            return $queryBuilder->getSQL();
        });
    }
}
