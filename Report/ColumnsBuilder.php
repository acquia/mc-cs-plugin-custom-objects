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
    private $columns;

    public function __construct(CustomObject $customObject)
    {
        $this->customObject = $customObject;
        $this->buildColumns();
    }

    private function buildColumns(): void
    {
        /** @var CustomField $customField */
        foreach ($this->customObject->getCustomFields() as $customField) {
            $this->columns[$this->getTableAlias($customField)] = [
                'label' => $customField->getLabel(),
                'type' => 'string',
            ];
        }
    }

    public function getColumns(): array
    {
        return $this->columns;
    }

    private function getTableAlias(CustomField $customField): string
    {
        return substr(md5($customField->getAlias()), 0, 8);
    }

    public function prepareQuery(QueryBuilder $queryBuilder): void
    {
        foreach ($this->customObject->getCustomFields() as $customField) {
            $tableAlias = $this->getTableAlias($customField);
        }
    }
}
