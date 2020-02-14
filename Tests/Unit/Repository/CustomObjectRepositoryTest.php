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

namespace MauticPlugin\CustomObjectsBundle\Tests\Unit\Repository;

use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Query\Expr;
use Doctrine\ORM\QueryBuilder;
use MauticPlugin\CustomObjectsBundle\Entity\CustomObject;
use MauticPlugin\CustomObjectsBundle\Repository\CustomObjectRepository;

class CustomObjectRepositoryTest extends \PHPUnit\Framework\TestCase
{
    private $entityManager;
    private $classMetadata;
    private $queryBuilder;
    private $query;
    private $expression;

    /**
     * @var CustomObjectRepository
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
        $this->repository    = new CustomObjectRepository(
            $this->entityManager,
            $this->classMetadata
        );

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
}
