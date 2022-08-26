<?php

declare(strict_types=1);

namespace MauticPlugin\CustomObjectsBundle\Report;

use Doctrine\DBAL\Query\QueryBuilder;
use MauticPlugin\CustomObjectsBundle\CustomFieldType\MultiselectType;
use MauticPlugin\CustomObjectsBundle\Entity\CustomField;
use MauticPlugin\CustomObjectsBundle\Entity\CustomObject;

class ReportColumnsBuilder
{
    public const DEFAULT_COLUMN_TYPE = 'string';

    /**
     * @var CustomObject
     */
    private $customObject;

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

    /**
     * This should always return same hash for same columns.
     * The hash prefix is to ensure MySql won't think hashes started with 8e* are number.
     */
    private function getHash(CustomField $customField): string
    {
        return '_'.substr(md5((string) $customField->getId()), 0, 8);
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

    public function getJoinableColumns(): array
    {
        $columns = [];
        foreach ($this->customObject->getCustomFields() as $customField) {
            if ($this->checkIfColumnHasToBeJoined($customField)) {
                $columns[] = $customField;
            }
        }

        return $columns;
    }

    public function joinReportColumns(QueryBuilder $queryBuilder, string $customItemTableAlias): void
    {
        /** @var CustomField $customField */
        foreach ($this->getJoinableColumns() as $customField) {
            $hash = $this->getHash($customField);
            if ($this->isMultiSelectTypeCustomField($customField)) {
                $joinQueryBuilder = new QueryBuilder($queryBuilder->getConnection());
                $joinQueryBuilder
                    ->from($customField->getTypeObject()->getPrefixedTableName())
                    ->select('custom_item_id', 'GROUP_CONCAT(value separator \', \') AS value')
                    ->andWhere('custom_field_id = '.$customField->getId())
                    ->groupBy('custom_item_id');
                $valueTableName = sprintf('(%s)', $joinQueryBuilder->getSQL());
                $joinCondition  = sprintf('`%s`.`id` = `%s`.`custom_item_id`', $customItemTableAlias, $hash);
            } else {
                $valueTableName = $customField->getTypeObject()->getPrefixedTableName();
                $joinCondition  = sprintf('`%s`.`id` = `%s`.`custom_item_id` AND `%s`.`custom_field_id` = %s', $customItemTableAlias, $hash, $hash, $customField->getId());
            }

            $queryBuilder->leftJoin($customItemTableAlias, $valueTableName, "`{$hash}`", $joinCondition);
        }
    }
}
