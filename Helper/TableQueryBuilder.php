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

namespace MauticPlugin\CustomObjectsBundle\Helper;

use Doctrine\ORM\QueryBuilder;
use MauticPlugin\CustomObjectsBundle\DTO\TableConfig;
use Doctrine\ORM\Mapping\ClassMetadata;
use MauticPlugin\CustomObjectsBundle\DTO\TableFilterConfig;
use Doctrine\ORM\Query\Expr\Comparison;

class TableQueryBuilder
{
    /**
     * @var TableConfig
     */
    private $tableConfig;

    /**
     * @var QueryBuilder
     */
    private $queryBuilder;

    /**
     * @var ClassMetadata
     */
    private $metadata;

    /**
     * @var string
     */
    private $rootAlias;

    /**
     * @param TableConfig   $tableConfig
     * @param QueryBuilder  $queryBuilder
     * @param ClassMetadata $metadata
     *
     * @return QueryBuilder
     */
    public function __construct(TableConfig $tableConfig, QueryBuilder $queryBuilder, ClassMetadata $metadata)
    {
        $this->tableConfig  = $tableConfig;
        $this->queryBuilder = $queryBuilder;
        $this->metadata     = $metadata;
        $this->rootAlias    = $this->queryBuilder->getRootAliases()[0];
    }

    /**
     * @return QueryBuilder
     */
    public function getTableDataQuery(): QueryBuilder
    {
        $this->queryBuilder->select($this->rootAlias);
        $this->queryBuilder->setMaxResults($this->tableConfig->getLimit());
        $this->queryBuilder->setFirstResult($this->tableConfig->getOffset());
        $this->queryBuilder->orderBy($this->tableConfig->getOrderBy(), $this->tableConfig->getOrderDirection());

        return $this->applyTableFilters();
    }

    /**
     * @return QueryBuilder
     */
    public function getTableCountQuery(): QueryBuilder
    {
        $this->queryBuilder->select($this->queryBuilder->expr()->count($this->rootAlias));

        return $this->applyTableFilters();
    }

    /**
     * @return QueryBuilder
     */
    private function applyTableFilters(): QueryBuilder
    {
        foreach ($this->tableConfig->getFilters() as $filters) {
            foreach ($filters as $filter) {
                $this->applyTableFilter($filter);
            }
        }

        return $this->queryBuilder;
    }

    /**
     * @param TableFilterConfig $filter
     */
    private function applyTableFilter(TableFilterConfig $filter): void
    {
        $this->addJoinIfNecessary($filter);

        if ('orX' === $filter->getExpression() && is_array($filter->getValue())) {
            $expr = $this->queryBuilder->expr()->orX();
            foreach ($filter->getValue() as $orFilter) {
                $expr->add($this->createExpr($orFilter));
            }
        } else {
            $expr = $this->createExpr($filter);
        }

        $this->queryBuilder->andWhere($expr);
    }

    /**
     * isNull and isNotNull returns string instead of Comparison.
     * 
     * @param TableFilterConfig $filter
     *
     * @return Comparison|string
     */
    private function createExpr(TableFilterConfig $filter)
    {
        $expr = $this->queryBuilder->expr()->{$filter->getExpression()}($filter->getFullColumnName(), ':'.$filter->getColumnName());
        $this->queryBuilder->setParameter($filter->getColumnName(), $filter->getValue());

        return $expr;
    }

    /**
     * @param TableFilterConfig $filter
     */
    private function addJoinIfNecessary(TableFilterConfig $filter): void
    {
        if (!in_array($filter->getTableAlias(), $this->queryBuilder->getAllAliases(), true)) {
            $cloumnNameArr = array_keys($this->metadata->getAssociationsByTargetClass($filter->getEntityName()));
            if (empty($cloumnNameArr[0])) {
                throw new \UnexpectedValueException("Entity {$filter->getEntityName()} does not have association with {$filter->getEntityName()}");
            }
            $this->queryBuilder->leftJoin($this->rootAlias.'.'.$cloumnNameArr[0], $filter->getTableAlias());
        }
    }
}
