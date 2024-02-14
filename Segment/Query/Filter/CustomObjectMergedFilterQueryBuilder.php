<?php

declare(strict_types=1);

namespace MauticPlugin\CustomObjectsBundle\Segment\Query\Filter;

use Mautic\LeadBundle\Segment\ContactSegmentFilter;
use Mautic\LeadBundle\Segment\Query\Filter\BaseFilterQueryBuilder;
use Mautic\LeadBundle\Segment\Query\QueryBuilder;
use Mautic\LeadBundle\Segment\RandomParameterName;
use MauticPlugin\CustomObjectsBundle\Helper\QueryFilterHelper;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class CustomObjectMergedFilterQueryBuilder extends BaseFilterQueryBuilder
{
    private QueryFilterHelper $queryFilterHelper;

    public function __construct(
        RandomParameterName $randomParameterNameService,
        EventDispatcherInterface $dispatcher,
        QueryFilterHelper $queryFilterHelper
    ) {
        parent::__construct($randomParameterNameService, $dispatcher);
        $this->queryFilterHelper = $queryFilterHelper;
    }

    public static function getServiceId(): string
    {
        return 'mautic.lead.query.builder.custom_object.merged.value';
    }

    public function applyQuery(QueryBuilder $queryBuilder, ContactSegmentFilter $filter): QueryBuilder
    {
        $leadsTableAlias = $queryBuilder->getTableAlias(MAUTIC_TABLE_PREFIX.'leads');
        $subQuery        = $this->queryFilterHelper->createMergeFilterQuery($filter, $leadsTableAlias);
        $queryBuilder->addLogic($queryBuilder->expr()->exists($subQuery->getSQL()), $filter->getGlue());
        $queryBuilder->setParameters($subQuery->getParameters(), $subQuery->getParameterTypes());

        return $queryBuilder;
    }
}
