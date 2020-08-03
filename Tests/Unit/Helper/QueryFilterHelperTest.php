<?php

declare(strict_types=1);

namespace MauticPlugin\CustomObjectsBundle\Tests\Unit\Helper;

use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManager;
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\LeadBundle\Segment\Query\Expression\ExpressionBuilder;
use Mautic\LeadBundle\Segment\Query\QueryBuilder;
use MauticPlugin\CustomObjectsBundle\Helper\CustomFieldQueryBuilder;
use MauticPlugin\CustomObjectsBundle\Helper\QueryFilterHelper;
use MauticPlugin\CustomObjectsBundle\Provider\CustomFieldTypeProvider;
use MauticPlugin\CustomObjectsBundle\Repository\CustomFieldRepository;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class QueryFilterHelperTest extends TestCase
{
    /**
     * @var QueryFilterHelper
     */
    private $queryFilterHelper;

    /**
     * @var QueryBuilder|MockObject
     */
    private $queryBuilder;

    /**
     * @var ExpressionBuilder|MockObject
     */
    private $expressionBuilder;

    protected function setUp(): void
    {
        parent::setUp();

        $entityManager = $this->createMock(EntityManager::class);
        $entityManager
            ->method('getConnection')
            ->willReturn($this->createMock(Connection::class));
        $coreParametersHelper = $this->createMock(CoreParametersHelper::class);
        $this->queryFilterHelper = new QueryFilterHelper(
            $entityManager,
            new CustomFieldQueryBuilder(
                $entityManager,
                new CustomFieldTypeProvider(),
                $coreParametersHelper,
                $this->createMock(CustomFieldRepository::class),
                new CustomFieldQueryBuilder\Calculator()
            )
        );
        $this->queryBuilder = $this->createMock(QueryBuilder::class);
        $this->expressionBuilder = $this->createMock(ExpressionBuilder::class);
    }

    /**
     * @doesNotPerformAssertions
     */
    public function testAddCustomObjectNameExpression(): void
    {
        $this->queryBuilder
            ->expects($this->any())
            ->method('expr')
            ->willReturn($this->expressionBuilder);

        $this->expressionBuilder
            ->expects($this->any())
            ->method('eq')
            ->willReturn($this->expressionBuilder);

        $this->queryBuilder
            ->expects($this->any())
            ->method('andWhere')
            ->with($this->expressionBuilder);

        $this->queryBuilder
            ->expects($this->any())
            ->method('setParameter')
            ->with('test_value_value', 'acquia', null);

        $this->queryFilterHelper
            ->addCustomObjectNameExpression($this->queryBuilder, 'test', 'eq', 'acquia');
    }

    public function testAddCustomObjectNameExpressionWithErrorForIntegerValue(): void
    {
        $this->expectException(\TypeError::class);
        $this->queryBuilder
            ->expects($this->any())
            ->method('expr')
            ->willReturn($this->expressionBuilder);

        $this->expressionBuilder
            ->expects($this->any())
            ->method('eq')
            ->willReturn($this->expressionBuilder);

        $this->queryBuilder
            ->expects($this->any())
            ->method('andWhere')
            ->with($this->expressionBuilder);

        $this->queryBuilder
            ->expects($this->any())
            ->method('setParameter')
            ->with('test_value_value', 10, null);

        $this->queryFilterHelper
            ->addCustomObjectNameExpression($this->queryBuilder, 'test', 'eq', 10);
    }
}
