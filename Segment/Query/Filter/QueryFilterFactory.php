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

use Mautic\LeadBundle\Segment\Query\QueryBuilder;
use MauticPlugin\CustomObjectsBundle\Helper\QueryFilterHelper;
use Mautic\LeadBundle\Segment\ContactSegmentFilterFactory;
use Doctrine\DBAL\Connection;
use MauticPlugin\CustomObjectsBundle\Exception\InvalidSegmentFilterException;

class QueryFilterFactory
{
    /**
     * @var Connection
     */
    private $connection;

    /**
     * @var ContactSegmentFilterFactory
     */
    private $filterFactory;

    /**
     * @var QueryFilterHelper
     */
    private $queryFilterHelper;

    /**
     * @param Connection                  $connection
     * @param ContactSegmentFilterFactory $filterFactory
     * @param QueryFilterHelper           $queryFilterHelper
     */
    public function __construct(
        Connection $connection,
        ContactSegmentFilterFactory $filterFactory,
        QueryFilterHelper $queryFilterHelper
    ) {
        $this->connection        = $connection;
        $this->filterFactory     = $filterFactory;
        $this->queryFilterHelper = $queryFilterHelper;
    }


    /**
     * @param mixed[] $segmentFilter
     *
     * @return QueryBuilder
     *
     * @throws InvalidSegmentFilterException
     * @throws \MauticPlugin\CustomObjectsBundle\Exception\NotFoundException
     * @throws \Exception
     */
    public function configureQueryBuilderFromSegmentFilter(array $segmentFilter, string $queryAlias): QueryBuilder
    {
        $segmentFilter = $this->filterFactory->factorSegmentFilter($segmentFilter);

        if ($segmentFilter->getTable() !== MAUTIC_TABLE_PREFIX.'custom_objects') {
            throw new InvalidSegmentFilterException("{$segmentFilter->getTable()} filter table cannot be processed.");
        }

        $type = $segmentFilter->getQueryType();

        if (CustomFieldFilterQueryBuilder::getServiceId() === $type) {
            $filterQueryBuilder = $this->queryFilterHelper->createValueQueryBuilder(
                $this->connection,
                $queryAlias,
                (int) $segmentFilter->getField(),
                $segmentFilter->getType()
            );
            $this->queryFilterHelper->addCustomFieldValueExpressionFromSegmentFilter($filterQueryBuilder, $queryAlias, $segmentFilter);
        } elseif (CustomItemFilterQueryBuilder::getServiceId() === $type) {
            $filterQueryBuilder = $this->queryFilterHelper->createItemNameQueryBuilder($this->connection, $queryAlias);
            $this->queryFilterHelper->addCustomObjectNameExpression(
                $filterQueryBuilder,
                $queryAlias,
                $segmentFilter->getOperator(),
                $segmentFilter->getParameterValue()
            );
        } else {
            throw new InvalidSegmentFilterException("{$type} filter query type cannot be processed.");
        }

        return $filterQueryBuilder;
    }
}
