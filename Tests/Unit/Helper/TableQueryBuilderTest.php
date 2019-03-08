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

namespace MauticPlugin\CustomObjectsBundle\Tests\Unit\Helper;

use MauticPlugin\CustomObjectsBundle\Helper\TableQueryBuilder;
use MauticPlugin\CustomObjectsBundle\DTO\TableConfig;
use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Query\Expr;
use MauticPlugin\CustomObjectsBundle\DTO\TableFilterConfig;
use MauticPlugin\CustomObjectsBundle\Entity\CustomObject;
use MauticPlugin\CustomObjectsBundle\Entity\CustomItemXrefContact;
use Doctrine\ORM\Query\Expr\Orx;
use Doctrine\ORM\Query\Expr\Comparison;

class TableQueryBuilderTest extends \PHPUnit_Framework_TestCase
{
    private const ROOT_ALIAS = 'CustomItem';

    private $tableConfig;
    private $queryBuilder;
    private $classMetadata;
    private $expr;
    private $exprComparison;
    private $tableQueryBuilder;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tableConfig       = $this->createMock(TableConfig::class);
        $this->queryBuilder      = $this->createMock(QueryBuilder::class);
        $this->classMetadata     = $this->createMock(ClassMetadata::class);
        $this->expr              = $this->createMock(Expr::class);
        $this->exprComparison    = $this->createMock(Comparison::class);
        $this->tableQueryBuilder = new TableQueryBuilder(
            $this->tableConfig,
            $this->queryBuilder,
            $this->classMetadata
        );

        $this->queryBuilder->method('getRootAliases')
            ->willReturn([self::ROOT_ALIAS]);
    }

    public function testGetTableDataQueryWithoutFilters(): void
    {
        $this->tableConfig->expects($this->once())
            ->method('getLimit')
            ->willReturn(10);

        $this->tableConfig->expects($this->once())
            ->method('getOffset')
            ->willReturn(30);

        $this->tableConfig->expects($this->once())
            ->method('getOrderBy')
            ->willReturn('CustomObject.id');

        $this->tableConfig->expects($this->once())
            ->method('getOrderDirection')
            ->willReturn('DESC');

        $this->queryBuilder->expects($this->once())
            ->method('select')
            ->willReturn(self::ROOT_ALIAS);

        $this->queryBuilder->expects($this->once())
            ->method('setMaxResults')
            ->willReturn(10);

        $this->queryBuilder->expects($this->once())
            ->method('setFirstResult')
            ->willReturn(30);

        $this->queryBuilder->expects($this->once())
            ->method('orderBy')
            ->willReturn('CustomObject.id', 'DESC');

        $this->assertSame($this->queryBuilder, $this->tableQueryBuilder->getTableDataQuery());
    }

    public function testGetTableCountQueryWithoutFilters(): void
    {
        $this->expr->expects($this->once())
            ->method('count')
            ->willReturn(self::ROOT_ALIAS);

        $this->queryBuilder->expects($this->once())
            ->method('expr')
            ->willReturn($this->expr);

        $this->queryBuilder->expects($this->once())
            ->method('select');

        $this->assertSame($this->queryBuilder, $this->tableQueryBuilder->getTableCountQuery());
    }

    public function testGetTableDataQueryWithFilters(): void
    {
        $filters = [
            CustomObject::class => [new TableFilterConfig(CustomObject::class, 'id', 45, 'eq')],
        ];

        $this->queryBuilder->expects($this->once())
            ->method('expr')
            ->willReturn($this->expr);

        $this->queryBuilder->expects($this->once())
            ->method('getAllAliases')
            ->willReturn([self::ROOT_ALIAS]);

        $this->tableConfig->expects($this->once())
            ->method('getFilters')
            ->willReturn($filters);

        $this->classMetadata->expects($this->once())
            ->method('getAssociationsByTargetClass')
            ->with(CustomObject::class)
            ->willReturn(['CustomObject' => []]);

        $this->queryBuilder->expects($this->once())
            ->method('leftJoin')
            ->willReturn('CustomItem.CustomObject', 'CustomObject');

        $this->expr->expects($this->once())
            ->method('eq')
            ->with('CustomObject.id', ':id')
            ->willReturn($this->exprComparison);

        $this->queryBuilder->expects($this->once())
            ->method('setParameter')
            ->with('id', 45);

        $this->queryBuilder->expects($this->once())
            ->method('andWhere')
            ->with($this->exprComparison);

        $this->assertSame($this->queryBuilder, $this->tableQueryBuilder->getTableDataQuery());
    }

    public function testGetTableDataQueryWithOrxFilters(): void
    {
        $contactId  = 445;
        $notContact = new TableFilterConfig(CustomItemXrefContact::class, 'contact', $contactId, 'neq');
        $isNull     = new TableFilterConfig(CustomItemXrefContact::class, 'contact', null, 'isNull');
        $orX        = new TableFilterConfig(CustomItemXrefContact::class, 'contact', [$notContact, $isNull], 'orX');
        $filters    = [CustomObject::class => [$orX]];
        $exprOrx    = $this->createMock(Orx::class);

        $this->queryBuilder->method('expr')
            ->willReturn($this->expr);

        $this->queryBuilder->expects($this->once())
            ->method('getAllAliases')
            ->willReturn([self::ROOT_ALIAS]);

        $this->tableConfig->expects($this->once())
            ->method('getFilters')
            ->willReturn($filters);

        $this->classMetadata->expects($this->once())
            ->method('getAssociationsByTargetClass')
            ->with(CustomItemXrefContact::class)
            ->willReturn(['CustomObject' => []]);

        $this->queryBuilder->expects($this->once())
            ->method('leftJoin')
            ->willReturn('CustomItem.CustomObject', 'CustomObject');

        $this->expr->expects($this->once())
            ->method('orX')
            ->willReturn($exprOrx);

        $this->expr->expects($this->once())
            ->method('neq')
            ->with('CustomItemXrefContact.contact', ':contact')
            ->willReturn($this->exprComparison);

        $this->expr->expects($this->once())
            ->method('isNull')
            ->with('CustomItemXrefContact.contact', ':contact')
            ->willReturn($this->exprComparison);

        $this->queryBuilder->expects($this->exactly(2))
            ->method('setParameter')
            ->withConsecutive(
                ['contact', $contactId],
                ['contact', null]
            );

        $this->queryBuilder->expects($this->once())
            ->method('andWhere')
            ->with($exprOrx);

        $this->assertSame($this->queryBuilder, $this->tableQueryBuilder->getTableDataQuery());
    }
}
