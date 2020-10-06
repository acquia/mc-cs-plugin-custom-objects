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
use MauticPlugin\CustomObjectsBundle\Entity\CustomField;
use MauticPlugin\CustomObjectsBundle\Entity\CustomObject;
use MauticPlugin\CustomObjectsBundle\Repository\CustomObjectRepository;
use PHPUnit\Framework\TestCase;

class CustomObjectRepositoryTest extends TestCase
{
    private $entityManager;
    private $classMetadata;
    private $queryBuilder;
    private $query;
    private $expression;

    /**
     * @var CustomObject
     */
    private $customObject;

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
        $this->customObject = $this->createMock(CustomObject::class);
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
        $customObjectId = random_int(1, 100);
        $this->customObject->expects($this->once())
            ->method('getId')
            ->willReturn($customObjectId);

        $customFields = $this->createCustomFields(2);
        $this->customObject->expects($this->once())
            ->method('getCustomFields')
            ->willReturn($customFields);

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

        $orX->expects($this->exactly(2))
            ->method('add');

        $this->query->expects($this->once())
            ->method('getResult')
            ->willReturn([]);

        $this->repository->getFilterSegments($this->customObject);
    }

    private function createCustomFields(int $quantity): array
    {
        $customFields = [];
        for ($id = 1; $id <= $quantity; ++$id) {
            $customField = new CustomField();
            $customField->setId($id);
            $customFields[] = $customField;
        }

        return $customFields;
    }
}
