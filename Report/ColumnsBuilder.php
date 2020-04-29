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
        $this->joinCustomFieldTable();
        $this->buildColumns();
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

    public function getColumns(): array
    {
        return $this->columns;
    }

    private function getHash(CustomField $customField): string
    {
        return substr(md5((string)$customField->getId()), 0, 8);
    }

    private function getColumnName(CustomField $customField): string
    {
        return $this->getHash($customField) . '.value';
    }

    private function joinCustomFieldTable(): void
    {
        if (1 > $this->customObject->getCustomFields()->count()) {
            return;
        }
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
            $valueTableName = $customField->getTypeObject()->getTableName();
            $joinCondition = sprintf('%s.id = %s.custom_item_id AND %s.custom_field_id = %s', $customItemTableAlias, $hash, $hash, $customField->getId());
            $queryBuilder->leftJoin($customItemTableAlias, $valueTableName, $hash, $joinCondition);
        }
    }
}
