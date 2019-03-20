<?php

declare(strict_types=1);

/*
 * @copyright   2019 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\CustomObjectsBundle\Tests\Unit\DTO;

use MauticPlugin\CustomObjectsBundle\DTO\TableConfig;
use MauticPlugin\CustomObjectsBundle\DTO\TableFilterConfig;
use MauticPlugin\CustomObjectsBundle\Entity\CustomObject;
use MauticPlugin\CustomObjectsBundle\Entity\CustomItem;
use MauticPlugin\CustomObjectsBundle\Exception\NotFoundException;
use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Query\Expr;

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

    public function testFilterWorkflow(): void
    {
        $tableConfig = $this->initTableConfig(self::LIMIT, 1);
        $filterA     = new TableFilterConfig(CustomObject::class, 'columnA', 'value A', 'lte');
        $filterC     = $tableConfig->createFilter(CustomObject::class, 'columnC', 'value C', 'like');

        $tableConfig->addFilterDTO($filterA);
        $tableConfig->addFilterDTO($filterC);
        $tableConfig->addFilter(CustomObject::class, 'columnB', 'value B');
        $tableConfig->addFilterIfNotEmpty(CustomItem::class, 'columnD', 'value D', 'like');
        $tableConfig->addFilterIfNotEmpty(CustomItem::class, 'columnE', null);

        $filters = $tableConfig->getFilters();
        $this->assertCount(2, $filters); // 2 entities
        $this->assertCount(3, $filters[CustomObject::class]); // 3 filters for this entity
        $this->assertCount(1, $filters[CustomItem::class]); // 1 filter for this entity

        $this->assertSame($filterC, $tableConfig->getFilter(CustomObject::class, 'columnC'));
        $this->assertTrue($tableConfig->hasFilter(CustomObject::class, 'columnC'));
        $this->assertFalse($tableConfig->hasFilter(CustomObject::class, 'columnZ'));

        $this->expectException(NotFoundException::class);
        $this->assertSame($filterC, $tableConfig->getFilter(CustomObject::class, 'columnX'));
    }

    public function testGetFilterIfEntityNotFound(): void
    {
        $tableConfig = $this->initTableConfig();
        $this->expectException(NotFoundException::class);
        $this->expectExceptionMessage('No filter for entity MauticPlugin\CustomObjectsBundle\Entity\CustomObject exists');
        $tableConfig->getFilter(CustomObject::class, 'unicorn');
    }

    public function testGetFilterIfColumnNotFound(): void
    {
        $tableConfig = $this->initTableConfig();
        $tableConfig->addFilter(CustomObject::class, 'columnB', 'value B');
        $this->expectException(NotFoundException::class);
        $this->expectExceptionMessage('Filter for entity MauticPlugin\CustomObjectsBundle\Entity\CustomObject and column blah does not exist');
        $tableConfig->getFilter(CustomObject::class, 'blah');
    }

    public function testConfigureSelectQueryBuilder(): void
    {
        $queryBuilder  = $this->createMock(QueryBuilder::class);
        $classMetadata = $this->createMock(ClassMetadata::class);
        $tableConfig   = $this->initTableConfig();

        $queryBuilder->expects($this->once())
            ->method('select');

        $configuredBuilder = $tableConfig->configureSelectQueryBuilder($queryBuilder, $classMetadata);
        $this->assertSame($queryBuilder, $configuredBuilder);
    }

    public function testConfigureCountQueryBuilder(): void
    {
        $queryBuilder  = $this->createMock(QueryBuilder::class);
        $classMetadata = $this->createMock(ClassMetadata::class);
        $expr          = $this->createMock(Expr::class);
        $tableConfig   = $this->initTableConfig();

        $queryBuilder->expects($this->once())
            ->method('expr')
            ->willReturn($expr);

        $queryBuilder->expects($this->once())
            ->method('select');

        $configuredBuilder = $tableConfig->configureCountQueryBuilder($queryBuilder, $classMetadata);
        $this->assertSame($queryBuilder, $configuredBuilder);
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
