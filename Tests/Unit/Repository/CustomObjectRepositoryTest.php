<?php

declare(strict_types=1);

namespace MauticPlugin\CustomObjectsBundle\Tests\Unit\Repository;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Query\Expr;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use MauticPlugin\CustomObjectsBundle\Entity\CustomField;
use MauticPlugin\CustomObjectsBundle\Entity\CustomObject;
use MauticPlugin\CustomObjectsBundle\Repository\CustomObjectRepository;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class CustomObjectRepositoryTest extends TestCase
{
    /**
     * @var MockObject&EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var MockObject&ClassMetadata
     */
    private $classMetadata;

    /**
     * @var MockObject&QueryBuilder
     */
    private $queryBuilder;

    /**
     * @var MockObject&AbstractQuery
     */
    private $query;

    /**
     * @var MockObject&Expr
     */
    private $expression;

    /**
     * @var CustomObjectRepository
     */
    private $repository;

    /**
     * @var MockObject|ManagerRegistry
     */
    private $registry;

    protected function setUp(): void
    {
        parent::setUp();

        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->classMetadata = $this->createMock(ClassMetadata::class);
        $this->queryBuilder  = $this->createMock(QueryBuilder::class);
        $this->query         = $this->createMock(AbstractQuery::class);
        $this->expression    = $this->createMock(Expr::class);
        $this->registry      = $this->createMock(ManagerRegistry::class);
        $this->repository    = new CustomObjectRepository(
            $this->registry,
        );

        $this->registry->method('getManagerForClass')->willReturn($this->entityManager);
        $this->entityManager->method('getClassMetadata')->willReturn($this->classMetadata);
        $this->entityManager->method('createQueryBuilder')->willReturn($this->queryBuilder);
        $this->queryBuilder->method('getQuery')->willReturn($this->query);
        $this->queryBuilder->method('expr')->willReturn($this->expression);
    }

    public function testCheckAliasExists(): void
    {
        $this->queryBuilder->expects($this->exactly(2))
            ->method('select')
            ->withConsecutive(
                [CustomObject::TABLE_ALIAS],
                ['count(CustomObject.id) as alias_count']
            )
            ->willReturnSelf();

        $this->queryBuilder->expects($this->once())
            ->method('from')
            ->willReturnSelf();

        $this->queryBuilder->expects($this->once())
            ->method('where')
            ->with('CustomObject.alias = :alias');

        $this->queryBuilder->expects($this->once())
            ->method('setParameter')
            ->with('alias', 'alias-1');

        $this->query->expects($this->once())
            ->method('getSingleResult')
            ->willReturn(['alias_count' => 10]);

        $this->assertTrue($this->repository->checkAliasExists('alias-1'));
    }

    public function testCheckAliasExistsWithId(): void
    {
        $this->queryBuilder->expects($this->exactly(2))
            ->method('select')
            ->withConsecutive(
                [CustomObject::TABLE_ALIAS],
                ['count(CustomObject.id) as alias_count']
            )
            ->willReturnSelf();

        $this->queryBuilder->expects($this->once())
            ->method('from')
            ->willReturnSelf();

        $this->queryBuilder->expects($this->once())
            ->method('where')
            ->with('CustomObject.alias = :alias');

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
            ->with('CustomObject.id', ':ignoreId');

        $this->query->expects($this->once())
            ->method('getSingleResult')
            ->willReturn(['alias_count' => 0]);

        $this->assertFalse($this->repository->checkAliasExists('alias-1', 444));
    }

    public function testGetTableAlias(): void
    {
        $this->assertSame(CustomObject::TABLE_ALIAS, $this->repository->getTableAlias());
    }

    public function testGetFilterSegmentsMethod(): void
    {
        $customObject = new class() extends CustomObject {
            public function getId()
            {
                return random_int(1, 100);
            }
        };

        $customObject->setCustomFields($this->createCustomFields(2));

        $this->queryBuilder->expects($this->once())
            ->method('select')
            ->with('l')
            ->willReturnSelf();

        $this->queryBuilder->expects($this->once())
            ->method('from')
            ->willReturnSelf();

        $orX = $this->createMock(Expr\Orx::class);
        $this->expression->expects($this->once())
            ->method('orX')
            ->willReturn($orX);

        $orX->expects($this->exactly(3))
            ->method('add');

        $this->query->expects($this->once())
            ->method('getResult')
            ->willReturn([]);

        $this->repository->getFilterSegments($customObject);
    }

    /**
     * @return ArrayCollection<int,CustomField>
     */
    private function createCustomFields(int $quantity): ArrayCollection
    {
        $customFields = [];
        for ($id = 1; $id <= $quantity; ++$id) {
            $customField = new CustomField();
            $customField->setId($id);
            $customFields[] = $customField;
        }

        return new ArrayCollection($customFields);
    }
}
