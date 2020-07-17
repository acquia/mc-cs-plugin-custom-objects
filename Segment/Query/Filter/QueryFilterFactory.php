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
     * @return UnionQueryContainer|SegmentQueryBuilder
     * @throws InvalidSegmentFilterException
     * @throws NotFoundException
     * @throws Exception
     */
    public function configureQueryBuilderFromSegmentFilter(array $segmentFilter, string $queryAlias)
    {
        $segmentFilter = $this->contactSegmentFilterFactory->factorSegmentFilter($segmentFilter);

        if ($segmentFilter->getTable() !== MAUTIC_TABLE_PREFIX.'custom_objects') {
            throw new InvalidSegmentFilterException("{$segmentFilter->getTable()} filter table cannot be processed.");
        }

        $type = $segmentFilter->getQueryType();

        if (CustomFieldFilterQueryBuilder::getServiceId() === $type) {
            $queryBuilder = $this->queryFilterHelper->createValueQuery(
                $queryAlias,
                $segmentFilter
            );
            $this->queryFilterHelper->addCustomFieldValueExpressionFromSegmentFilter(
                $queryBuilder,
                $queryAlias,
                $segmentFilter
            );
        } elseif (CustomItemNameFilterQueryBuilder::getServiceId() === $type) {
            $queryBuilder = $this->queryFilterHelper->createItemNameQueryBuilder($queryAlias);

            $this->queryFilterHelper->addCustomObjectNameExpression(
                $queryBuilder,
                $queryAlias,
                $segmentFilter->getOperator(),
                $segmentFilter->getParameterValue()
            );
        } else {
            throw new InvalidSegmentFilterException("{$type} filter query type cannot be processed.");
        }

        return $queryBuilder;
    }
}
