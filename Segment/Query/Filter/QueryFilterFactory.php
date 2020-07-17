<?php

declare(strict_types=1);

/*
 * @copyright   2019 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\CustomObjectsBundle\Segment\Query\Filter;

use Mautic\LeadBundle\Segment\ContactSegmentFilterFactory;
use Mautic\LeadBundle\Segment\Query\QueryBuilder as SegmentQueryBuilder;
use MauticPlugin\CustomObjectsBundle\Exception\InvalidSegmentFilterException;
use MauticPlugin\CustomObjectsBundle\Exception\NotFoundException;
use MauticPlugin\CustomObjectsBundle\Helper\QueryFilterHelper;
use MauticPlugin\CustomObjectsBundle\Segment\Query\UnionQueryContainer;

class QueryFilterFactory
{
    /**
     * @var ContactSegmentFilterFactory
     */
    private $contactSegmentFilterFactory;

    /**
     * @var QueryFilterHelper
     */
    private $queryFilterHelper;

    public function __construct(
        ContactSegmentFilterFactory $filterFactory,
        QueryFilterHelper $queryFilterHelper
    ) {
        $this->contactSegmentFilterFactory = $filterFactory;
        $this->queryFilterHelper           = $queryFilterHelper;
    }

    /**
     * @throws InvalidSegmentFilterException
     * @throws NotFoundException
     * @throws \Exception
     */
    public function configureQueryBuilderFromSegmentFilterForField(array $segmentFilter, string $queryAlias): UnionQueryContainer
    {
        $segmentFilter = $this->contactSegmentFilterFactory->factorSegmentFilter($segmentFilter);

        if ($segmentFilter->getTable() !== MAUTIC_TABLE_PREFIX.'custom_objects') {
            throw new InvalidSegmentFilterException("{$segmentFilter->getTable()} filter table cannot be processed.");
        }

        $type = $segmentFilter->getQueryType();

        if (CustomFieldFilterQueryBuilder::getServiceId() !== $type) {
            throw new InvalidSegmentFilterException("{$type} filter query type cannot be processed.");
        };

        $unionQueryContainer = $this->queryFilterHelper->createValueQuery(
            $queryAlias,
            $segmentFilter
        );
        $this->queryFilterHelper->addCustomFieldValueExpressionFromSegmentFilter(
            $unionQueryContainer,
            $queryAlias,
            $segmentFilter
        );

        return $unionQueryContainer;
    }

    /**
     * @throws InvalidSegmentFilterException
     * @throws \Exception
     */
    public function configureQueryBuilderFromSegmentFilterForName(array $segmentFilter, string $queryAlias): SegmentQueryBuilder
    {
        $segmentFilter = $this->contactSegmentFilterFactory->factorSegmentFilter($segmentFilter);

        if ($segmentFilter->getTable() !== MAUTIC_TABLE_PREFIX.'custom_objects') {
            throw new InvalidSegmentFilterException("{$segmentFilter->getTable()} filter table cannot be processed.");
        }

        $type = $segmentFilter->getQueryType();

        if (CustomItemNameFilterQueryBuilder::getServiceId() !== $type) {
            throw new InvalidSegmentFilterException("{$type} filter query type cannot be processed.");
        }

        $filterQueryBuilder = $this->queryFilterHelper->createItemNameQueryBuilder($queryAlias);

        $this->queryFilterHelper->addCustomObjectNameExpression(
            $filterQueryBuilder,
            $queryAlias,
            $segmentFilter->getOperator(),
            $segmentFilter->getParameterValue()
        );

        return $filterQueryBuilder;
    }
}
