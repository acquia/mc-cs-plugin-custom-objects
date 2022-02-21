<?php

declare(strict_types=1);

namespace MauticPlugin\CustomObjectsBundle\Tests\Unit\Model;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Query\QueryBuilder;
use Doctrine\ORM\EntityManager;
use MauticPlugin\CustomObjectsBundle\Model\CustomFieldOptionModel;

class CustomFieldOptionModelTest extends \PHPUnit\Framework\TestCase
{
    private $entityManager;
    private $connection;
    private $queryBuilder;

    /**
     * @var CustomFieldOptionModel
     */
    private $customFieldOptionModel;

    protected function setUp(): void
    {
        parent::setUp();

        defined('MAUTIC_TABLE_PREFIX') || define('MAUTIC_TABLE_PREFIX', '');

        $this->entityManager          = $this->createMock(EntityManager::class);
        $this->connection             = $this->createMock(Connection::class);
        $this->queryBuilder           = $this->createMock(QueryBuilder::class);
        $this->customFieldOptionModel = new CustomFieldOptionModel($this->entityManager);

        $this->entityManager->method('getConnection')->willReturn($this->connection);
        $this->connection->method('createQueryBuilder')->willReturn($this->queryBuilder);
    }

    public function testDeleteByCustomFieldId(): void
    {
        $this->queryBuilder->expects($this->once())
            ->method('delete')
            ->with(MAUTIC_TABLE_PREFIX.'custom_field_option');

        $this->queryBuilder->expects($this->once())
            ->method('where')
            ->with('custom_field_id = :customFieldId');

        $this->queryBuilder->expects($this->once())
            ->method('setParameter')
            ->with('customFieldId', 123);

        $this->queryBuilder->expects($this->once())
            ->method('execute');

        $this->customFieldOptionModel->deleteByCustomFieldId(123);
    }
}
