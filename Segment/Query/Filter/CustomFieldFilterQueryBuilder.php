<?php

declare(strict_types=1);

namespace MauticPlugin\CustomObjectsBundle\Segment\Query\Filter;

use Doctrine\DBAL\DBALException;
use Mautic\LeadBundle\Segment\ContactSegmentFilter;
use Mautic\LeadBundle\Segment\Query\Filter\BaseFilterQueryBuilder;
use Mautic\LeadBundle\Segment\Query\QueryBuilder as SegmentQueryBuilder;
use Mautic\LeadBundle\Segment\RandomParameterName;
use MauticPlugin\CustomObjectsBundle\Helper\QueryFilterHelper;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class CustomFieldFilterQueryBuilder extends BaseFilterQueryBuilder
{
    public function __construct(
        RandomParameterName $randomParameterNameService,
        EventDispatcherInterface $dispatcher,
        private QueryFilterHelper $filterHelper
    ) {
        parent::__construct($randomParameterNameService, $dispatcher);
    }

    /** {@inheritdoc} */
    public static function getServiceId(): string
    {
        return 'mautic.lead.query.builder.custom_field.value';
    }

    /**
     * @throws DBALException
     */
    public function applyQuery(SegmentQueryBuilder $queryBuilder, ContactSegmentFilter $filter): SegmentQueryBuilder
    {
        $leadsTableAlias = $queryBuilder->getTableAlias(MAUTIC_TABLE_PREFIX.'leads');
        $filterOperator  = $filter->getOperator();

        $tableAlias = 'cfwq_'.(int) $filter->getField();

        $unionQueryContainer = $this->filterHelper->createValueQuery($tableAlias, $filter, true);

        foreach ($unionQueryContainer as $segmentQueryBuilder) {
            $segmentQueryBuilder->andWhere(
                $segmentQueryBuilder->expr()->eq("{$tableAlias}_contact.contact_id", $leadsTableAlias.'.id')
            );
        }

        match ($filterOperator) {
            'empty', 'neq', 'notLike', '!multiselect', '!between', 'notBetween' => $queryBuilder->addLogic(
                $queryBuilder->expr()->notExists($unionQueryContainer->getSQL()),
                $filter->getGlue()
            ),
            default => $queryBuilder->addLogic(
                $queryBuilder->expr()->exists($unionQueryContainer->getSQL()),
                $filter->getGlue()
            ),
        };

        $queryBuilder->setParameters($unionQueryContainer->getParameters(), $unionQueryContainer->getParameterTypes());

        return $queryBuilder;
    }
}
