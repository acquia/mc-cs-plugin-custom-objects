<?php

declare(strict_types=1);

namespace MauticPlugin\CustomObjectsBundle\Tests\Unit\Repository;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Query\Expression\ExpressionBuilder;
use Doctrine\DBAL\Query\QueryBuilder as DbalQueryBuilder;
use Doctrine\DBAL\Statement;
use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Query\Expr;
use Doctrine\ORM\QueryBuilder;
use Mautic\LeadBundle\Entity\Lead;
use MauticPlugin\CustomObjectsBundle\DTO\TableConfig;
use MauticPlugin\CustomObjectsBundle\Entity\CustomItem;
use MauticPlugin\CustomObjectsBundle\Entity\CustomItemXrefContact;
use MauticPlugin\CustomObjectsBundle\Entity\CustomObject;
use MauticPlugin\CustomObjectsBundle\Repository\CustomItemXrefContactRepository;

class CustomItemXrefContactRepositoryTest extends \PHPUnit\Framework\TestCase
{
    private $entityManager;
    private $classMetadata;
    private $contact;
    private $queryBuilder;
    private $queryBuilderDbal;
    private $connection;
    private $statement;
    private $expr;
    private $expressionBuilder;
    private $query;

    /**
     * @var CustomItemXrefContactRepository
     */
    private $repository;

    protected function setUp(): void
    {
        parent::setUp();

        defined('MAUTIC_TABLE_PREFIX') || define('MAUTIC_TABLE_PREFIX', '');

        $this->entityManager        = $this->createMock(EntityManager::class);
        $this->classMetadata        = $this->createMock(ClassMetadata::class);
        $this->contact              = $this->createMock(Lead::class);
        $this->queryBuilder         = $this->createMock(QueryBuilder::class);
        $this->queryBuilderDbal     = $this->createMock(DbalQueryBuilder::class);
        $this->connection           = $this->createMock(Connection::class);
        $this->statement            = $this->createMock(Statement::class);
        $this->expr                 = $this->createMock(Expr::class);
        $this->expressionBuilder    = $this->createMock(ExpressionBuilder::class);
        $this->query                = $this->createMock(AbstractQuery::class);
        $this->repository           = new CustomItemXrefContactRepository(
            $this->entityManager,
            $this->classMetadata
        );

        $this->entityManager->method('createQueryBuilder')->willReturn($this->queryBuilder);
        $this->entityManager->method('getConnection')->willReturn($this->connection);
        $this->connection->method('createQueryBuilder')->willReturn($this->queryBuilderDbal);
        $this->queryBuilder->method('select')->willReturnSelf();
        $this->queryBuilder->method('from')->willReturnSelf();
        $this->queryBuilder->method('expr')->willReturn($this->expr);
        $this->queryBuilderDbal->method('expr')->willReturn($this->expressionBuilder);
        $this->queryBuilderDbal->method('execute')->willReturn($this->statement);
        $this->queryBuilder->method('getQuery')->willReturn($this->query);
    }

    public function testCountItemsLinkedToContact(): void
    {
        $contactId   = 53;
        $limit       = 10;
        $page        = 2;
        $order       = 'CustomItem.dateAdded';
        $orderDir    = 'DESC';
        $tableConfig = new TableConfig($limit, $page, $order, $orderDir);

        $this->contact->expects($this->once())
            ->method('getId')
            ->willReturn($contactId);

        $this->queryBuilder->expects($this->exactly(2))
            ->method('select')
            ->withConsecutive([CustomItemXrefContact::TABLE_ALIAS], [CustomObject::TABLE_ALIAS.'.id']);

        $this->queryBuilder->expects($this->once())
            ->method('addSelect')
            ->with(CustomObject::TABLE_ALIAS.'.alias');

        $this->queryBuilder->expects($this->once())
            ->method('from')
            ->with(null, 'CustomItemXrefContact', null);

        $this->queryBuilder->expects($this->exactly(2))
            ->method('innerJoin')
            ->withConsecutive(
                [CustomItemXrefContact::TABLE_ALIAS.'.customItem', CustomItem::TABLE_ALIAS],
                [CustomItem::TABLE_ALIAS.'.customObject', CustomObject::TABLE_ALIAS]
            );

        $this->queryBuilder->expects($this->once())
            ->method('where')
            ->with(CustomItemXrefContact::TABLE_ALIAS.'.contact = :contactId');

        $this->queryBuilder->expects($this->once())
            ->method('andWhere')
            ->with(CustomObject::TABLE_ALIAS.'.isPublished = 1');

        $this->queryBuilder->expects($this->once())
            ->method('groupBy')
            ->with(CustomObject::TABLE_ALIAS.'.id');

        $this->queryBuilder->expects($this->once())
            ->method('setParameter')
            ->with('contactId', $contactId);

        $this->queryBuilder->expects($this->once())
            ->method('setMaxResults')
            ->with($limit);

        $this->queryBuilder->expects($this->once())
            ->method('setFirstResult')
            ->with(10);

        $this->queryBuilder->expects($this->once())
            ->method('orderBy')
            ->with($order, $orderDir);

        $this->query->expects($this->once())
            ->method('getResult')
            ->willReturn(['some items here']);

        $this->assertSame(
            ['some items here'],
            $this->repository->getCustomObjectsRelatedToContact(
                $this->contact,
                $tableConfig
            )
        );
    }

    public function testGetTableAlias(): void
    {
        $this->assertSame(CustomItemXrefContact::TABLE_ALIAS, $this->repository->getTableAlias());
    }
}
