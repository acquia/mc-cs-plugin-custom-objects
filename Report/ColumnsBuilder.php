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
            $this->columns[$this->getHash($customField) . '.value'] = [
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

    private function joinCustomFieldTable(): void
    {
        if (1 > $this->customObject->getCustomFields()->count()) {
            return;
        }


    }

    public function prepareQuery(QueryBuilder $queryBuilder): void
    {
        foreach ($this->customObject->getCustomFields() as $customField) {
            $hash = $this->getHash($customField);
            //$valueTableName = $customField->getTypeObject()->getTableName();
            //$joinExpression = sprintf('%s.id = %s.custom_field_id', static::CUSTOM_ITEM_XREF_COMPANY_PREFIX, static::COMPANIES_TABLE_PREFIX);
            //$queryBuilder->leftJoin(static::CUSTOM_ITEM_XREF_COMPANY_ALIAS, $tableName, $tableAlias, $joinExpression);
            //$queryBuilder->where()
        }
    }
}
