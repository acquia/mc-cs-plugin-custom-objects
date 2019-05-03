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

namespace MauticPlugin\CustomObjectsBundle\DTO;

use MauticPlugin\CustomObjectsBundle\Exception\NotFoundException;
use Doctrine\ORM\QueryBuilder;
use MauticPlugin\CustomObjectsBundle\Helper\TableQueryBuilder;
use Doctrine\ORM\Mapping\ClassMetadata;
use Mautic\CoreBundle\Helper\ArrayHelper;

class TableConfig
{
    /**
     * @var int
     */
    private $limit;

    /**
     * @var int
     */
    private $page;

    /**
     * @var string
     */
    private $orderBy;

    /**
     * @var string
     */
    private $orderDirection;

    /**
     * @var mixed[]
     */
    private $filters = [];

    /**
     * @var mixed[]
     */
    private $parameters = [];

    /**
     * @param int    $limit
     * @param int    $page
     * @param string $orderBy
     * @param string $orderDirection
     */
    public function __construct(int $limit, int $page, string $orderBy, string $orderDirection = 'ASC')
    {
        $this->limit          = $limit;
        $this->page           = $page;
        $this->orderBy        = $orderBy;
        $this->orderDirection = $orderDirection;
    }

    /**
     * @return string
     */
    public function getOrderBy(): string
    {
        return $this->orderBy;
    }

    /**
     * @return string
     */
    public function getOrderDirection(): string
    {
        return $this->orderDirection;
    }

    /**
     * @return int
     */
    public function getOffset(): int
    {
        $offset = 1 === $this->page ? 0 : (($this->page - 1) * $this->limit);

        return $offset < 0 ? 0 : $offset;
    }

    /**
     * @return int
     */
    public function getLimit(): int
    {
        return $this->limit;
    }

    /**
     * @param string $key
     * @param mixed  $value
     */
    public function addParameter(string $key, $value)
    {
        $this->parameters[$key] = $value;
    }

    /**
     * @param string $key
     * @param mixed  $defaultValue
     * 
     * @return mixed
     */
    public function getParameter(string $key, $defaultValue = null)
    {
        return ArrayHelper::getValue($key, $this->parameters, $defaultValue);
    }

    /**
     * @param string $entityName
     * @param string $columnName
     * @param mixed  $value
     * @param string $expression
     */
    public function addFilter(string $entityName, string $columnName, $value, string $expression = 'eq'): void
    {
        $this->addFilterDTO($this->createFilter($entityName, $columnName, $value, $expression));
    }

    /**
     * @param TableFilterConfig $tableFilterConfig
     */
    public function addFilterDTO(TableFilterConfig $tableFilterConfig): void
    {
        if (!isset($this->filters[$tableFilterConfig->getEntityName()])) {
            $this->filters[$tableFilterConfig->getEntityName()] = [];
        }

        $this->filters[$tableFilterConfig->getEntityName()][] = $tableFilterConfig;
    }

    /**
     * @param string $entityName
     * @param string $columnName
     * @param mixed  $value
     * @param string $expression
     *
     * @return TableFilterConfig
     */
    public function createFilter(string $entityName, string $columnName, $value, string $expression = 'eq'): TableFilterConfig
    {
        return new TableFilterConfig($entityName, $columnName, $value, $expression);
    }

    /**
     * Checks if the filter value is not empty before adding the filter.
     *
     * @param string $entityName
     * @param string $columnName
     * @param mixed  $value
     * @param string $expression
     */
    public function addFilterIfNotEmpty(string $entityName, string $columnName, $value, string $expression = 'eq'): void
    {
        // Remove SQL wild cards for NOT/LIKE:
        if (in_array($expression, ['like', 'notLike'], true) && is_string($value)) {
            $trimmedValue = trim($value, '%');
        } else {
            $trimmedValue = $value;
        }

        if (!empty($trimmedValue)) {
            $this->addFilter($entityName, $columnName, $value, $expression);
        }
    }

    /**
     * @return mixed[]
     */
    public function getFilters(): array
    {
        return $this->filters;
    }

    /**
     * @param string $entityName
     * @param string $columnName
     *
     * @return TableFilterConfig
     *
     * @throws NotFoundException
     */
    public function getFilter(string $entityName, string $columnName): TableFilterConfig
    {
        if (empty($this->filters[$entityName])) {
            throw new NotFoundException("No filter for entity {$entityName} exists");
        }

        foreach ($this->filters[$entityName] as $filter) {
            if ($filter->getColumnName() === $columnName) {
                return $filter;
            }
        }

        throw new NotFoundException("Filter for entity {$entityName} and column {$columnName} does not exist");
    }

    /**
     * @param string $entityName
     * @param string $columnName
     *
     * @return bool
     */
    public function hasFilter(string $entityName, string $columnName): bool
    {
        try {
            $this->getFilter($entityName, $columnName);

            return true;
        } catch (NotFoundException $e) {
            return false;
        }
    }

    /**
     * @param QueryBuilder  $queryBuilder
     * @param ClassMetadata $metadata
     *
     * @return QueryBuilder
     */
    public function configureSelectQueryBuilder(QueryBuilder $queryBuilder, ClassMetadata $metadata): QueryBuilder
    {
        return $this->configureQueryBuilder($queryBuilder, $metadata)->getTableDataQuery();
    }

    /**
     * @param QueryBuilder  $queryBuilder
     * @param ClassMetadata $metadata
     *
     * @return QueryBuilder
     */
    public function configureCountQueryBuilder(QueryBuilder $queryBuilder, ClassMetadata $metadata): QueryBuilder
    {
        return $this->configureQueryBuilder($queryBuilder, $metadata)->getTableCountQuery();
    }

    /**
     * @param QueryBuilder  $queryBuilder
     * @param ClassMetadata $metadata
     *
     * @return TableQueryBuilder
     */
    private function configureQueryBuilder(QueryBuilder $queryBuilder, ClassMetadata $metadata): TableQueryBuilder
    {
        return new TableQueryBuilder($this, $queryBuilder, $metadata);
    }
}
