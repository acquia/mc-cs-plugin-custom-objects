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

namespace MauticPlugin\CustomObjectsBundle\Repository;

use Mautic\CoreBundle\Entity\CommonRepository;
use MauticPlugin\CustomObjectsBundle\DTO\TableConfig;
use Doctrine\ORM\QueryBuilder;

class AbstractTableRepository extends CommonRepository
{
    public const TABLE_ALIAS = 'undefined';

    /**
     * @param TableConfig $tableConfig
     *
     * @return QueryBuilder
     */
    public function getTableDataQuery(TableConfig $tableConfig): QueryBuilder
    {
        $queryBuilder = $this->createQueryBuilder(static::TABLE_ALIAS, static::TABLE_ALIAS.'.id');

        return $tableConfig->configureSelectQueryBuilder($queryBuilder, $this->getClassMetadata());
    }

    /**
     * @param TableConfig $tableConfig
     *
     * @return QueryBuilder
     */
    public function getTableCountQuery(TableConfig $tableConfig): QueryBuilder
    {
        $queryBuilder = $this->createQueryBuilder(static::TABLE_ALIAS, static::TABLE_ALIAS.'.id');

        return $tableConfig->configureCountQueryBuilder($queryBuilder, $this->getClassMetadata());
    }

    /**
     * @param QueryBuilder $queryBuilder
     * @param int          $userId
     *
     * @return QueryBuilder
     */
    public function applyOwnerId(QueryBuilder $queryBuilder, int $userId): QueryBuilder
    {
        return $queryBuilder->andWhere(static::TABLE_ALIAS.'.createdBy', $userId);
    }

    /**
     * @return string
     */
    public function getTableAlias(): string
    {
        return static::TABLE_ALIAS;
    }
}
