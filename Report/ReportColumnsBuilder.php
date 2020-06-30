<?php

declare(strict_types=1);

/*
 * @copyright   2020 Mautic, Inc. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.com
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\CustomObjectsBundle\Report;

use Doctrine\DBAL\Query\QueryBuilder;
use MauticPlugin\CustomObjectsBundle\CustomFieldType\MultiselectType;
use MauticPlugin\CustomObjectsBundle\Entity\CustomField;
use MauticPlugin\CustomObjectsBundle\Entity\CustomObject;

class ReportColumnsBuilder
{
    const DEFAULT_COLUMN_TYPE = 'string';

    /**
     * @var CustomObject
     */
    private $customObject;

    /**
     * @var CustomObject
     */
    private $parentCustomObject;

    /**
     * @var array
     */
    private $columns = [];

    /**
     * @var callable
     */
    private $callback;

    /**
     * @var array
     */
    protected $columnTypeMapping = [
        'int'      => 'int',
        'date'     => 'date',
        'datetime' => 'datetime',
    ];

    public function __construct(CustomObject $customObject)
    {
        $this->customObject = $customObject;
        if (CustomObject::TYPE_RELATIONSHIP === $customObject->getType()) {
            $this->parentCustomObject = $customObject->getMasterObject();
        }
    }

    private function buildColumns(): void
    {
        /** @var CustomField $customField */
        foreach ($this->customObject->getCustomFields() as $customField) {
            $this->columns[$this->getColumnName($customField)] = [
                'label' => $customField->getLabel(),
                'type'  => $this->resolveColumnType($customField),
            ];
        }

        if (!$this->parentCustomObject) {
            return;
        }

        /** @var CustomField $parentCustomField */
        foreach ($this->parentCustomObject->getCustomFields() as $parentCustomField) {
            $this->columns[$this->getColumnName($parentCustomField)] = [
                'label' => $parentCustomField->getLabel() . ' (parent)',
                'type'  => $this->resolveColumnType($parentCustomField),
            ];
        }
    }

    private function resolveColumnType(CustomField $customField): string
    {
        return $this->columnTypeMapping[$customField->getType()] ?? static::DEFAULT_COLUMN_TYPE;
    }

    private function isMultiSelectTypeCustomField(CustomField $customField): bool
    {
        return $customField->getTypeObject() instanceof MultiselectType;
    }

    public function getColumns(): array
    {
        if (1 > count($this->columns)) {
            $this->buildColumns();
        }

        return $this->columns;
    }

    private function getHash(CustomField $customField): string
    {
        // This should always return same hash for same columns
        return substr(md5((string) $customField->getId()), 0, 8);
    }

    private function getColumnName(CustomField $customField): string
    {
        return sprintf('%s.value', $this->getHash($customField));
    }

    public function setFilterColumnsCallback(callable $callback): void
    {
        $this->callback = $callback;
    }

    private function checkIfColumnHasToBeJoined(CustomField $customField): bool
    {
        if (!is_callable($this->callback)) {
            // Join all columns by default
            return true;
        }

        return call_user_func($this->callback, $this->getColumnName($customField));
    }

    private function getJoinableColumns(CustomObject $customObject): array
    {
        $columns = [];
        foreach ($customObject->getCustomFields() as $customField) {
            if ($this->checkIfColumnHasToBeJoined($customField)) {
                $columns[] = $customField;
            }
        }

        return $columns;
    }

    public function joinReportColumns(QueryBuilder $queryBuilder, string $customItemTableAlias): void
    {
        $columns = $this->getJoinableColumns($this->customObject);
        if (1 > count($columns)) {
            return;
        }

        $this->joinCustomObjectColumns($queryBuilder, $columns, $customItemTableAlias);

        if (!$this->parentCustomObject) {
            return;
        }

        $parentColumns = $this->getJoinableColumns($this->parentCustomObject);
        if (1 > count($parentColumns)) {
            return;
        }

        $this->joinCustomObjectColumns($queryBuilder, $parentColumns, $customItemTableAlias);
    }

    private function joinCustomObjectColumns(QueryBuilder $queryBuilder, array $columns, string $customItemTableAlias)
    {
        /** @var CustomField $customField */
        foreach ($columns as $customField) {
            $hash = $this->getHash($customField);
            if ($this->isMultiSelectTypeCustomField($customField)) {
                $joinQueryBuilder = new QueryBuilder($queryBuilder->getConnection());
                $joinQueryBuilder
                    ->from($customField->getTypeObject()->getTableName())
                    ->select('custom_item_id', 'GROUP_CONCAT(value separator \', \') AS value')
                    ->andWhere('custom_field_id = '.$customField->getId())
                    ->groupBy('custom_item_id');
                $valueTableName = sprintf('(%s)', $joinQueryBuilder->getSQL());
                $joinCondition  = sprintf('%s.id = %s.custom_item_id', $customItemTableAlias, $hash);
            } else {
                $valueTableName = $customField->getTypeObject()->getTableName();
                $joinCondition  = sprintf('%s.id = %s.custom_item_id AND %s.custom_field_id = %s', $customItemTableAlias, $hash, $hash, $customField->getId());
            }

            $queryBuilder->leftJoin($customItemTableAlias, $valueTableName, $hash, $joinCondition);
        }
    }
}
