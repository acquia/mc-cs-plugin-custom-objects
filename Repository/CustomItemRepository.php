<?php

declare(strict_types=1);

/*
 * @copyright   2018 Mautic, Inc. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.com
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\CustomObjectsBundle\Repository;

use Mautic\CoreBundle\Entity\CommonRepository;
use MauticPlugin\CustomObjectsBundle\DTO\TableConfig;
use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\Query\Expr;

class CustomItemRepository extends CommonRepository
{
    /**
     * @param TableConfig $tableConfig
     * 
     * @return QueryBuilder
     */
    public function getTableDataQuery(TableConfig $tableConfig): QueryBuilder
    {
        $alias        = self::getAlias();
        $queryBuilder = $this->createQueryBuilder($alias, $alias.'.id');
        $queryBuilder->select($alias);
        $queryBuilder->orderBy($tableConfig->getOrderBy(), $tableConfig->getOrderDirection());

        return $this->applyTableFilters($queryBuilder, $tableConfig);
    }

    /**
     * @param QueryBuilder $queryBuilder
     * @param TableConfig $tableConfig
     * 
     * @return QueryBuilder
     */
    public function applyTableFilters(QueryBuilder $queryBuilder, TableConfig $tableConfig): QueryBuilder
    {
        $aliases   = $queryBuilder->getAllAliases();
        $rootAlias = $queryBuilder->getRootAliases()[0];
        foreach ($tableConfig->getFilters() as $entityClass => $filters) {
            foreach ($filters as $filter) {
                $alias = self::getAlias($entityClass);
                if (!in_array($alias, $aliases)) {
                    $queryBuilder->innerJoin($rootAlias.'.contactsReference', $alias);
                }
                $queryBuilder->andWhere(
                    $queryBuilder->expr()->andX(
                        $queryBuilder->expr()->{$filter['expr']}($alias.'.'.$filter['column'], ':'.$filter['column'])
                    )
                );
                $queryBuilder->setParameter($filter['column'], $filter['value']);
            }
        }

        return $queryBuilder;
    }

    /**
     * @param QueryBuilder $queryBuilder
     * @param integer $userId
     * 
     * @return QueryBuilder
     */
    public function applyOwnerId(QueryBuilder $queryBuilder, int $userId): QueryBuilder
    {
        return $queryBuilder->andWhere(self::getAlias().'.createdBy', $userId);
    }

    /**
     * @param string $repositoryName
     * 
     * @return string
     */
    public static function getAlias(string $repositoryName = null): string
    {
        if (null === $repositoryName) {
            $repositoryName = self::class;
        }
        $path = explode('\\', $repositoryName);
        return rtrim(end($path), 'Repository');
    }
}
