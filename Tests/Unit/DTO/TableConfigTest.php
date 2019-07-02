<?php

declare(strict_types=1);

/*
 * @copyright   2019 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc
 *
 * @link        https://mautic.com
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\CustomObjectsBundle\Tests\Unit\DTO;

use MauticPlugin\CustomObjectsBundle\DTO\TableConfig;
use Doctrine\ORM\QueryBuilder as OrmQueryBuilder;
use Doctrine\DBAL\Query\QueryBuilder as DbalQueryBuilder;

class TableConfigTest extends \PHPUnit_Framework_TestCase
{
    private const LIMIT = 10;

    private const PAGE = 3;

    private const ORDER_BY = 'table.id';

    private const ORDER_BY_DIR = 'DESC';

    public function testGetters(): void
    {
        $tableConfig = $this->initTableConfig();

        $this->assertSame(self::ORDER_BY, $tableConfig->getOrderBy());
        $this->assertSame(self::ORDER_BY_DIR, $tableConfig->getOrderDirection());
        $this->assertSame(self::LIMIT, $tableConfig->getLimit());
        $this->assertSame(20, $tableConfig->getOffset());
    }

    /**
     * Page 3 should show items 20 - 30, so offset is 20.
     */
    public function testGetOffsetForPage3(): void
    {
        $tableConfig = $this->initTableConfig();

        $this->assertSame(20, $tableConfig->getOffset());
    }

    /**
     * Page 3 should show items 0 - 10, so offset is 0.
     */
    public function testGetOffsetForPage1(): void
    {
        $tableConfig = $this->initTableConfig(self::LIMIT, 1);

        $this->assertSame(0, $tableConfig->getOffset());
    }

    public function testConfigureOrmQueryBuilder(): void
    {
        $tableConfig = $this->initTableConfig(self::LIMIT, 1);
        $builder     = $this->createMock(OrmQueryBuilder::class);

        $builder->expects($this->once())
            ->method('setMaxResults')
            ->with(self::LIMIT);

        $builder->expects($this->once())
            ->method('setFirstResult')
            ->with(0);

        $builder->expects($this->once())
            ->method('orderBy')
            ->with(self::ORDER_BY, self::ORDER_BY_DIR);

        $tableConfig->configureOrmQueryBuilder($builder);
    }

    public function testConfigureDbalQueryBuilder(): void
    {
        $tableConfig = $this->initTableConfig(self::LIMIT, 1);
        $builder     = $this->createMock(DbalQueryBuilder::class);

        $builder->expects($this->once())
            ->method('setMaxResults')
            ->with(self::LIMIT);

        $builder->expects($this->once())
            ->method('setFirstResult')
            ->with(0);

        $builder->expects($this->once())
            ->method('orderBy')
            ->with(self::ORDER_BY, self::ORDER_BY_DIR);

        $tableConfig->configureDbalQueryBuilder($builder);
    }

    /**
     * @param int    $limit
     * @param int    $page
     * @param string $orderBy
     * @param string $orderDirection
     *
     * @return TableConfig
     */
    private function initTableConfig(
        int $limit = self::LIMIT,
        int $page = self::PAGE,
        string $orderBy = self::ORDER_BY,
        string $orderDirection = self::ORDER_BY_DIR
    ): TableConfig {
        return new TableConfig(
            $limit,
            $page,
            $orderBy,
            $orderDirection
        );
    }
}
