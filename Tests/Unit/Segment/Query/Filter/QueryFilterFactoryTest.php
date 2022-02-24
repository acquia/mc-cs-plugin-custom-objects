<?php

declare(strict_types=1);

namespace MauticPlugin\CustomObjectsBundle\Tests\Unit\Segment\Query\Filter;

use Mautic\LeadBundle\Segment\ContactSegmentFilter;
use Mautic\LeadBundle\Segment\ContactSegmentFilterFactory;
use Mautic\LeadBundle\Segment\Query\QueryBuilder as SegmentQueryBuilder;
use MauticPlugin\CustomObjectsBundle\Exception\InvalidSegmentFilterException;
use MauticPlugin\CustomObjectsBundle\Helper\QueryFilterHelper;
use MauticPlugin\CustomObjectsBundle\Segment\Query\Filter\CustomFieldFilterQueryBuilder;
use MauticPlugin\CustomObjectsBundle\Segment\Query\Filter\CustomItemNameFilterQueryBuilder;
use MauticPlugin\CustomObjectsBundle\Segment\Query\Filter\QueryFilterFactory;
use MauticPlugin\CustomObjectsBundle\Segment\Query\UnionQueryContainer;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class QueryFilterFactoryTest extends TestCase
{
    /**
     * @var ContactSegmentFilterFactory|MockObject
     */
    private $contactSegmentFilterFactory;

    /**
     * @var QueryFilterHelper|MockObject
     */
    private $queryFilterHelper;

    /**
     * @var ContactSegmentFilter|MockObject
     */
    private $contactSegmentFilter;

    /**
     * @var SegmentQueryBuilder|MockObject
     */
    private $segmentQueryBuilder;

    /**
     * @var UnionQueryContainer|MockObject
     */
    private $unionQueryContainer;

    /**
     * @var QueryFilterFactory
     */
    private $factory;

    protected function setUp(): void
    {
        parent::setUp();

        defined('MAUTIC_TABLE_PREFIX') || define('MAUTIC_TABLE_PREFIX', '');

        $this->contactSegmentFilterFactory = $this->createMock(ContactSegmentFilterFactory::class);
        $this->queryFilterHelper           = $this->createMock(QueryFilterHelper::class);
        $this->contactSegmentFilter        = $this->createMock(ContactSegmentFilter::class);
        $this->segmentQueryBuilder         = $this->createMock(SegmentQueryBuilder::class);
        $this->unionQueryContainer         = $this->createMock(UnionQueryContainer::class);
        $this->factory                     = new QueryFilterFactory(
            $this->contactSegmentFilterFactory,
            $this->queryFilterHelper
        );
    }

    public function testConfigureQueryBuilderFromSegmentFilterWithNotCustomObjectFilter(): void
    {
        $queryAlias    = 'filter_0';
        $segmentFilter = ['not relevant'];

        $this->contactSegmentFilterFactory->expects($this->once())
            ->method('factorSegmentFilter')
            ->with($segmentFilter)
            ->willReturn($this->contactSegmentFilter);

        $this->contactSegmentFilter->expects($this->exactly(2))
            ->method('getTable')
            ->willReturn('unicorn');

        $this->contactSegmentFilter->expects($this->never())
            ->method('getQueryType');

        $this->expectException(InvalidSegmentFilterException::class);

        $this->factory->configureQueryBuilderFromSegmentFilter($segmentFilter, $queryAlias);
    }

    public function testConfigureQueryBuilderFromSegmentFilterForCustomField(): void
    {
        $fieldId       = 1;
        $fieldType     = 'text';
        $queryAlias    = 'filter_0';
        $segmentFilter = [
            'glue'     => 'and',
            'field'    => 'cmf_'.$fieldId,
            'object'   => 'custom_object',
            'type'     => $fieldType,
            'filter'   => '23',
            'display'  => null,
            'operator' => '=',
        ];

        $this->contactSegmentFilterFactory->expects($this->once())
            ->method('factorSegmentFilter')
            ->with($segmentFilter)
            ->willReturn($this->contactSegmentFilter);

        $this->contactSegmentFilter->expects($this->once())
            ->method('getTable')
            ->willReturn(MAUTIC_TABLE_PREFIX.'custom_objects');

        $this->contactSegmentFilter->expects($this->once())
            ->method('getQueryType')
            ->willReturn(CustomFieldFilterQueryBuilder::getServiceId());

        $this->queryFilterHelper->expects($this->once())
            ->method('createValueQuery')
            ->with(
                $queryAlias,
                $this->contactSegmentFilter
            )
            ->willReturn($this->unionQueryContainer);

        $this->assertSame(
            $this->unionQueryContainer,
            $this->factory->configureQueryBuilderFromSegmentFilter($segmentFilter, $queryAlias)
        );
    }

    public function testConfigureQueryBuilderFromSegmentFilterForCustomItem(): void
    {
        $fieldId       = 1;
        $fieldType     = 'text';
        $operator      = '=';
        $value         = '23';
        $queryAlias    = 'filter_0';
        $segmentFilter = [
            'glue'     => 'and',
            'field'    => 'cmf_'.$fieldId,
            'object'   => 'custom_object',
            'type'     => $fieldType,
            'filter'   => $value,
            'display'  => null,
            'operator' => $operator,
        ];

        $this->contactSegmentFilterFactory->expects($this->once())
            ->method('factorSegmentFilter')
            ->with($segmentFilter)
            ->willReturn($this->contactSegmentFilter);

        $this->contactSegmentFilter->expects($this->once())
            ->method('getTable')
            ->willReturn(MAUTIC_TABLE_PREFIX.'custom_objects');

        $this->contactSegmentFilter->expects($this->once())
            ->method('getQueryType')
            ->willReturn(CustomItemNameFilterQueryBuilder::getServiceId());

        $this->contactSegmentFilter->expects($this->once())
            ->method('getOperator')
            ->willReturn($operator);

        $this->contactSegmentFilter->expects($this->once())
            ->method('getParameterValue')
            ->willReturn($value);

        $this->queryFilterHelper->expects($this->once())
            ->method('createItemNameQueryBuilder')
            ->with($queryAlias)
            ->willReturn($this->segmentQueryBuilder);

        $this->queryFilterHelper->expects($this->once())
            ->method('addCustomObjectNameExpression')
            ->with(
                $this->segmentQueryBuilder,
                $queryAlias,
                $operator,
                $value
            );

        $this->assertSame(
            $this->segmentQueryBuilder,
            $this->factory->configureQueryBuilderFromSegmentFilter($segmentFilter, $queryAlias)
        );
    }

    public function testConfigureQueryBuilderFromSegmentFilterForInvalidType(): void
    {
        $queryAlias    = 'filter_0';
        $segmentFilter = [
            'glue'     => 'and',
            'field'    => 'cmf_1',
            'object'   => 'custom_object',
            'type'     => 'text',
            'filter'   => '23',
            'display'  => null,
            'operator' => '=',
        ];

        $this->contactSegmentFilterFactory->expects($this->once())
            ->method('factorSegmentFilter')
            ->with($segmentFilter)
            ->willReturn($this->contactSegmentFilter);

        $this->contactSegmentFilter->expects($this->once())
            ->method('getTable')
            ->willReturn(MAUTIC_TABLE_PREFIX.'custom_objects');

        $this->contactSegmentFilter->expects($this->once())
            ->method('getQueryType')
            ->willReturn('unicorn');

        $this->expectException(InvalidSegmentFilterException::class);
        $this->factory->configureQueryBuilderFromSegmentFilter($segmentFilter, $queryAlias);
    }
}
