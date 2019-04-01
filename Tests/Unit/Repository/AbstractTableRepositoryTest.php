<?php

declare(strict_types=1);

/*
 * @copyright   2019 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\CustomObjectsBundle\Tests\Unit\Repository;

use MauticPlugin\CustomObjectsBundle\Repository\AbstractTableRepository;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\DBAL\Connection;
use MauticPlugin\CustomObjectsBundle\DTO\TableConfig;

class AbstractTableRepositoryTest extends \PHPUnit_Framework_TestCase
{
    private $entityManager;
    private $classMetadata;
    private $queryBuilder;
    private $connection;
    private $tableConfig;
    private $customTableRepository;

    protected function setUp(): void
    {
        parent::setUp();

        defined('MAUTIC_TABLE_PREFIX') or define('MAUTIC_TABLE_PREFIX', '');
        
        $this->entityManager = $this->createMock(EntityManager::class);
        $this->queryBuilder  = $this->createMock(QueryBuilder::class);
        $this->connection    = $this->createMock(Connection::class);
        $this->tableConfig   = $this->createMock(TableConfig::class);
        $this->classMetadata = $this->getMockBuilder(ClassMetadata::class)
            ->setConstructorArgs([AbstractTableRepository::class])->getMock();

        $this->customTableRepository = new AbstractTableRepository(
            $this->entityManager,
            $this->classMetadata
        );

        $this->entityManager->method('createQueryBuilder')->willReturn($this->queryBuilder);
        $this->queryBuilder->method('select')->willReturnSelf();
        $this->queryBuilder->method('from')->willReturnSelf();
    }

    public function testGetTableDataQuery(): void
    {
        $this->queryBuilder->expects($this->once())
            ->method('select')
            ->with('undefined');

        $this->queryBuilder->expects($this->once())
            ->method('from')
            ->with(AbstractTableRepository::class, 'undefined', 'undefined.id');

        $this->tableConfig->expects($this->once())
            ->method('configureSelectQueryBuilder')
            ->with($this->queryBuilder, $this->classMetadata);

        $this->customTableRepository->getTableDataQuery($this->tableConfig);
    }

    public function testGetTableCountQuery(): void
    {
        $this->queryBuilder->expects($this->once())
            ->method('select')
            ->with('undefined');

        $this->queryBuilder->expects($this->once())
            ->method('from')
            ->with(AbstractTableRepository::class, 'undefined', 'undefined.id');

        $this->tableConfig->expects($this->once())
            ->method('configureCountQueryBuilder')
            ->with($this->queryBuilder, $this->classMetadata);

        $this->customTableRepository->getTableCountQuery($this->tableConfig);
    }

    public function testApplyOwnerId(): void
    {
        $this->queryBuilder->expects($this->once())
            ->method('andWhere')
            ->with('undefined.createdBy', 55)
            ->willReturnSelf();

        $this->customTableRepository->applyOwnerId($this->queryBuilder, 55);
    }

    public function testGetTableAlias(): void
    {
        $this->assertSame('undefined', $this->customTableRepository->getTableAlias());
    }
}
