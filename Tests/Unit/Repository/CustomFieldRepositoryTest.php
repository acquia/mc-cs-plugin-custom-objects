<?php

declare(strict_types=1);

namespace MauticPlugin\CustomObjectsBundle\Tests\Unit\Repository;

use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Query\Expr;
use Doctrine\ORM\QueryBuilder;
use MauticPlugin\CustomObjectsBundle\Entity\CustomField;
use MauticPlugin\CustomObjectsBundle\Repository\CustomFieldRepository;

class CustomFieldRepositoryTest extends \PHPUnit\Framework\TestCase
{
    private $entityManager;
    private $classMetadata;
    private $queryBuilder;
    private $query;
    private $expression;

    /**
     * @var CustomFieldRepository
     */
    private $repository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->entityManager = $this->createMock(EntityManager::class);
        $this->classMetadata = $this->createMock(ClassMetadata::class);
        $this->queryBuilder  = $this->createMock(QueryBuilder::class);
        $this->query         = $this->createMock(AbstractQuery::class);
        $this->expression    = $this->createMock(Expr::class);
        $this->repository    = new CustomFieldRepository(
            $this->entityManager,
            $this->classMetadata
        );

        $this->entityManager->method('createQueryBuilder')->willReturn($this->queryBuilder);
        $this->queryBuilder->method('getQuery')->willReturn($this->query);
        $this->queryBuilder->method('expr')->willReturn($this->expression);
    }

    public function testIsAliasUnique(): void
    {
        $this->queryBuilder->expects($this->exactly(2))
            ->method('select')
            ->withConsecutive(
                [CustomField::TABLE_ALIAS],
                ['count(CustomField.id) as alias_count']
            )
            ->willReturnSelf();

        $this->queryBuilder->expects($this->once())
            ->method('from')
            ->willReturnSelf();

        $this->queryBuilder->expects($this->once())
            ->method('where')
            ->with('CustomField.alias = :alias');

        $this->queryBuilder->expects($this->once())
            ->method('setParameter')
            ->with('alias', 'alias-1');

        $this->query->expects($this->once())
            ->method('getSingleResult')
            ->willReturn(['alias_count' => 10]);

        $this->assertTrue($this->repository->isAliasUnique('alias-1'));
    }

    public function testIsAliasUniqueWithId(): void
    {
        $this->queryBuilder->expects($this->exactly(2))
            ->method('select')
            ->withConsecutive(
                [CustomField::TABLE_ALIAS],
                ['count(CustomField.id) as alias_count']
            )
            ->willReturnSelf();

        $this->queryBuilder->expects($this->once())
            ->method('from')
            ->willReturnSelf();

        $this->queryBuilder->expects($this->once())
            ->method('where')
            ->with('CustomField.alias = :alias');

        $this->queryBuilder->expects($this->once())
            ->method('andWhere');

        $this->queryBuilder->expects($this->exactly(2))
            ->method('setParameter')
            ->withConsecutive(
                ['alias', 'alias-1'],
                ['ignoreId', 444]
            );

        $this->expression->expects($this->once())
            ->method('neq')
            ->with('CustomField.id', ':ignoreId');

        $this->query->expects($this->once())
            ->method('getSingleResult')
            ->willReturn(['alias_count' => 0]);

        $this->assertFalse($this->repository->isAliasUnique('alias-1', 444));
    }

    public function testGetRequiredCustomFieldsForCustomObject(): void
    {
        $this->queryBuilder->expects($this->once())
            ->method('select')
            ->with(CustomField::TABLE_ALIAS)
            ->willReturnSelf();

        $this->queryBuilder->expects($this->once())
            ->method('from')
            ->willReturnSelf();

        $this->queryBuilder->expects($this->once())
            ->method('where')
            ->with('CustomField.customObject = :customObjectId');

        $this->queryBuilder->expects($this->once())
            ->method('andWhere')
            ->with('CustomField.required = :required');

        $this->queryBuilder->expects($this->exactly(2))
            ->method('setParameter')
            ->withConsecutive(
                ['customObjectId', 456],
                ['required', true]
            );

        $customField = $this->createMock(CustomField::class);

        $this->query->expects($this->once())
            ->method('getResult')
            ->willReturn([$customField]);

        $collection = $this->repository->getRequiredCustomFieldsForCustomObject(456);

        $this->assertCount(1, $collection);
        $this->assertSame($customField, $collection->current());
    }
}
