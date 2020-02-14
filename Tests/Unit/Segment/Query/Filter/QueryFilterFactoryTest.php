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

namespace MauticPlugin\CustomObjectsBundle\Tests\Unit\Segment\Query\Filter;

use Doctrine\DBAL\Connection;
use Mautic\LeadBundle\Segment\ContactSegmentFilter;
use Mautic\LeadBundle\Segment\ContactSegmentFilterFactory;
use Mautic\LeadBundle\Segment\Query\QueryBuilder;
use MauticPlugin\CustomObjectsBundle\Exception\InvalidSegmentFilterException;
use MauticPlugin\CustomObjectsBundle\Helper\QueryFilterHelper;
use MauticPlugin\CustomObjectsBundle\Segment\Query\Filter\CustomFieldFilterQueryBuilder;
use MauticPlugin\CustomObjectsBundle\Segment\Query\Filter\CustomItemNameFilterQueryBuilder;
use MauticPlugin\CustomObjectsBundle\Segment\Query\Filter\QueryFilterFactory;

class QueryFilterFactoryTest extends \PHPUnit\Framework\TestCase
{
    private $connection;
    private $contactSegmentFilterFactory;
    private $queryFilterHelper;
    private $contactSegmentFilter;
    private $queryBuilder;

    /**
     * @var QueryFilterFactory
     */
    private $factory;

    protected function setUp(): void
    {
        parent::setUp();

        defined('MAUTIC_TABLE_PREFIX') or define('MAUTIC_TABLE_PREFIX', '');

        $this->connection                  = $this->createMock(Connection::class);
        $this->contactSegmentFilterFactory = $this->createMock(ContactSegmentFilterFactory::class);
        $this->queryFilterHelper           = $this->createMock(QueryFilterHelper::class);
        $this->contactSegmentFilter        = $this->createMock(ContactSegmentFilter::class);
        $this->queryBuilder                = $this->createMock(QueryBuilder::class);
        $this->factory                     = new QueryFilterFactory(
            $this->connection,
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
            ->willReturn('custom_objects');

        $this->contactSegmentFilter->expects($this->once())
            ->method('getQueryType')
            ->willReturn(CustomFieldFilterQueryBuilder::getServiceId());

        $this->contactSegmentFilter->expects($this->once())
            ->method('getField')
            ->willReturn($fieldId);

        $this->contactSegmentFilter->expects($this->once())
            ->method('getType')
            ->willReturn($fieldType);

        $this->queryFilterHelper->expects($this->once())
            ->method('createValueQueryBuilder')
            ->with(
                $this->connection,
                $queryAlias,
                1,
                $fieldType
            )
            ->willReturn($this->queryBuilder);

        $this->queryFilterHelper->expects($this->once())
            ->method('addCustomFieldValueExpressionFromSegmentFilter')
            ->with(
                $this->queryBuilder,
                $queryAlias,
                $this->contactSegmentFilter
            );

        $this->assertSame(
            $this->queryBuilder,
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
            ->willReturn('custom_objects');

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
            ->with(
                $this->connection,
                $queryAlias
            )
            ->willReturn($this->queryBuilder);

        $this->queryFilterHelper->expects($this->once())
            ->method('addCustomObjectNameExpression')
            ->with(
                $this->queryBuilder,
                $queryAlias,
                $operator,
                $value
            );

        $this->assertSame(
            $this->queryBuilder,
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
            ->willReturn('custom_objects');

        $this->contactSegmentFilter->expects($this->once())
            ->method('getQueryType')
            ->willReturn('unicorn');

        $this->expectException(InvalidSegmentFilterException::class);
        $this->factory->configureQueryBuilderFromSegmentFilter($segmentFilter, $queryAlias);
    }
}
