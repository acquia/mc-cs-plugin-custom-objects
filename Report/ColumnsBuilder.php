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

class ColumnsBuilder
{
    /**
     * @var CustomObject
     */
    private $customObject;

    /**
     * @var array
     */
    private $columns = [];

    /**
     * @var \Closure
     */
    private $callback;

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
                'type' => 'string',
            ];
        }
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
        return substr(md5((string)$customField->getId()), 0, 8);
    }

    private function getColumnName(CustomField $customField): string
    {
        return $this->getHash($customField) . '.value';
    }

    public function setValidateColumnCallback(\Closure $callback): ColumnsBuilder
    {
        $this->callback = $callback;
        return $this;
    }

    private function checkIfColumnHasToBeJoined(CustomField $customField): bool
    {
        if (!is_callable($this->callback)) {
            return true;
        }

        return call_user_func($this->callback, $this->getColumnName($customField));
    }

    public function prepareQuery(QueryBuilder $queryBuilder, string $customItemTableAlias): void
    {
        /** @var CustomField $customField */
        foreach ($this->customObject->getCustomFields() as $customField) {
            if (!$this->checkIfColumnHasToBeJoined($customField)) {
                continue;
            }

            $hash = $this->getHash($customField);
            if ($this->isMultiSelectTypeCustomField($customField)) {
                $joinQueryBuilder = new QueryBuilder($queryBuilder->getConnection());
                $joinQueryBuilder
                    ->from($customField->getTypeObject()->getTableName())
                    ->select('custom_item_id', 'GROUP_CONCAT(value separator \', \') AS value')
                    ->andWhere('custom_field_id = ' . $customField->getId())
                    ->groupBy('custom_item_id');
                $joinCondition = sprintf('%s.id = %s.custom_item_id', $customItemTableAlias, $hash);
                $valueTableName = sprintf('(%s)', $joinQueryBuilder->getSQL());
            }
            else {
                $valueTableName = $customField->getTypeObject()->getTableName();
                $joinCondition = sprintf('%s.id = %s.custom_item_id AND %s.custom_field_id = %s', $customItemTableAlias, $hash, $hash, $customField->getId());
            }

            $queryBuilder->leftJoin($customItemTableAlias, $valueTableName, $hash, $joinCondition);
        }
    }
}
