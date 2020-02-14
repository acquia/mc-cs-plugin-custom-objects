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

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\EntityManager;
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

            if ($customFieldValue->getCustomField()->canHaveMultipleValues()) {
                $customFieldValue->addValue($row['value']);
            } else {
                $customFieldValue->setValue($row['value']);
            }
        });
    }

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
