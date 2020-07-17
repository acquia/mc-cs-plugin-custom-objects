<?php

declare(strict_types=1);

namespace MauticPlugin\CustomObjectsBundle\Tests\Unit\Segment\Query\Filter;

use Doctrine\DBAL\Connection;
use Mautic\LeadBundle\{Segment\ContactSegmentFilter,
    Segment\Query\Expression\ExpressionBuilder,
    Segment\Query\Filter\FilterQueryBuilderInterface,
    Segment\Query\QueryBuilder,
    Segment\RandomParameterName};
use MauticPlugin\CustomObjectsBundle\Helper\QueryFilterHelper;
use MauticPlugin\CustomObjectsBundle\Provider\CustomFieldTypeProvider;
use MauticPlugin\CustomObjectsBundle\Segment\Query\Filter\CustomItemNameFilterQueryBuilder;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class CustomItemNameFilterQueryBuilderTest extends TestCase
{
    /**
     * @var FilterQueryBuilderInterface
     */
    private $customItemNameFilterQueryBuilder;

    /**
     * @var QueryBuilder|MockObject
     */
    private $queryBuilder;

    /**
     * @var ContactSegmentFilter|MockObject
     */
    private $contactSegmentFilter;

    /**
     * @var Connection|MockObject
     */
    private $connection;

    /**
     * @var ExpressionBuilder|MockObject
     */
    private $expressionBuilder;

    protected function setUp(): void
    {
        parent::setUp();
        defined('MAUTIC_TABLE_PREFIX') or define('MAUTIC_TABLE_PREFIX', '');

        $randomParameter = new RandomParameterName();
        $eventDispatcher = $this->createMock(EventDispatcherInterface::class);
        $customFieldTypeProvider = $this->createMock(CustomFieldTypeProvider::class);
        $queryFilterHelper = new QueryFilterHelper($customFieldTypeProvider);
        $this->queryBuilder = $this->createMock(QueryBuilder::class);
        $this->contactSegmentFilter = $this->createMock(ContactSegmentFilter::class);
        $this->connection = $this->createMock(Connection::class);
        $this->expressionBuilder = $this->createMock(ExpressionBuilder::class);

        $this->customItemNameFilterQueryBuilder = new CustomItemNameFilterQueryBuilder(
            $randomParameter,
            $queryFilterHelper,
            $eventDispatcher
        );
    }

    public function testApplyQuery(): void
    {
        $this->contactSegmentFilter
            ->expects($this->at(0))
            ->method("getField")
            ->willReturn("field1");

        $this->contactSegmentFilter
            ->expects($this->at(1))
            ->method("getField")
            ->willReturn("1");

        $this->contactSegmentFilter
            ->expects($this->at(2))
            ->method("getOperator")
            ->willReturn("eq");

        $this->contactSegmentFilter
            ->expects($this->at(3))
            ->method("getParameterValue")
            ->willReturn("mautic");

        $this->contactSegmentFilter
            ->expects($this->at(4))
            ->method("getOperator")
            ->willReturn("eq");

        $this->queryBuilder
            ->expects($this->any())
            ->method("getConnection")
            ->willReturn($this->connection);

        $this->queryBuilder
            ->expects($this->any())
            ->method('expr')
            ->willReturn($this->expressionBuilder);

        $result = $this->customItemNameFilterQueryBuilder->applyQuery($this->queryBuilder, $this->contactSegmentFilter);
        $this->assertEquals($this->queryBuilder, $result);
    }

    public function testApplyQueryWithIntegerParameterValue(): void
    {
        $this->contactSegmentFilter
            ->expects($this->at(0))
            ->method("getField")
            ->willReturn("field1");

        $this->contactSegmentFilter
            ->expects($this->at(1))
            ->method("getField")
            ->willReturn("1");

        $this->contactSegmentFilter
            ->expects($this->at(2))
            ->method("getOperator")
            ->willReturn("eq");

        $this->contactSegmentFilter
            ->expects($this->at(3))
            ->method("getParameterValue")
            ->willReturn(10);

        $this->contactSegmentFilter
            ->expects($this->at(4))
            ->method("getOperator")
            ->willReturn("eq");

        $this->queryBuilder
            ->expects($this->any())
            ->method("getConnection")
            ->willReturn($this->connection);

        $this->queryBuilder
            ->expects($this->any())
            ->method('expr')
            ->willReturn($this->expressionBuilder);

        $result = $this->customItemNameFilterQueryBuilder->applyQuery($this->queryBuilder, $this->contactSegmentFilter);
        $this->assertEquals($this->queryBuilder, $result);
    }
}
