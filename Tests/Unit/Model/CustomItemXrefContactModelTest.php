<?php

declare(strict_types=1);

namespace MauticPlugin\CustomObjectsBundle\Tests\Unit\Model;

use DateTime;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Driver\Statement;
use Doctrine\DBAL\Query\QueryBuilder as DBALQueryBuilder;
use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\QueryBuilder;
use MauticPlugin\CustomObjectsBundle\Entity\CustomItem;
use MauticPlugin\CustomObjectsBundle\Model\CustomItemXrefContactModel;
use Symfony\Contracts\Translation\TranslatorInterface;

class CustomItemXrefContactModelTest extends \PHPUnit\Framework\TestCase
{
    private $customItem;

    private $entityManager;

    private $queryBuilder;

    private $query;

    private $translator;

    /**
     * @var CustomItemXrefContactModel
     */
    private $customItemXrefContactModel;

    protected function setUp(): void
    {
        parent::setUp();

        $this->customItem                   = $this->createMock(CustomItem::class);
        $this->entityManager                = $this->createMock(EntityManager::class);
        $this->queryBuilder                 = $this->createMock(QueryBuilder::class);
        $this->query                        = $this->createMock(AbstractQuery::class);
        $this->translator                   = $this->createMock(TranslatorInterface::class);
        $this->customItemXrefContactModel   = new CustomItemXrefContactModel(
            $this->entityManager,
            $this->translator
        );

        $this->entityManager->method('createQueryBuilder')->willReturn($this->queryBuilder);
        $this->queryBuilder->method('getQuery')->willReturn($this->query);
    }

    public function testGetLinksLineChartData(): void
    {
        defined('MAUTIC_TABLE_PREFIX') || define('MAUTIC_TABLE_PREFIX', '');

        $from         = new DateTime('2019-03-02 12:30:00');
        $to           = new DateTime('2019-04-02 12:30:00');
        $connection   = $this->createMock(Connection::class);
        $queryBuilder = $this->createMock(DBALQueryBuilder::class);
        $statement    = $this->createMock(Statement::class);

        $this->entityManager->expects($this->once())
            ->method('getConnection')
            ->willReturn($connection);

        $connection->expects($this->once())
            ->method('createQueryBuilder')
            ->willReturn($queryBuilder);

        $queryBuilder->expects($this->once())
            ->method('execute')
            ->willReturn($statement);

        $statement->expects($this->once())
            ->method('fetchAll')
            ->willReturn([]);

        $chartData = $this->customItemXrefContactModel->getLinksLineChartData(
            $from,
            $to,
            $this->customItem
        );

        $this->assertCount(32, $chartData['labels']);
        $this->assertCount(32, $chartData['datasets'][0]['data']);
    }

    public function testGetPermissionBase(): void
    {
        $this->assertSame(
            'custom_objects:custom_items',
            $this->customItemXrefContactModel->getPermissionBase()
        );
    }
}
