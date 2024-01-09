<?php

declare(strict_types=1);

namespace MauticPlugin\CustomObjectsBundle\Segment\Query\Filter;

use Doctrine\DBAL\DBALException;
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\LeadBundle\Segment\ContactSegmentFilter;
use Mautic\LeadBundle\Segment\Query\Filter\BaseFilterQueryBuilder;
use Mautic\LeadBundle\Segment\Query\QueryBuilder as SegmentQueryBuilder;
use Mautic\LeadBundle\Segment\RandomParameterName;
use MauticPlugin\CustomObjectsBundle\Helper\QueryFilterHelper;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class CustomFieldFilterQueryBuilder extends BaseFilterQueryBuilder
{
    /**
     * @var QueryFilterHelper
     */
    private $filterHelper;

    private CoreParametersHelper $coreParametersHelper;

    public function __construct(
        RandomParameterName $randomParameterNameService,
        EventDispatcherInterface $dispatcher,
        QueryFilterHelper $filterHelper,
        CoreParametersHelper $coreParametersHelper
    ) {
        parent::__construct($randomParameterNameService, $dispatcher);

        $this->filterHelper         = $filterHelper;
        $this->coreParametersHelper = $coreParametersHelper;
    }

    /** {@inheritdoc} */
    public static function getServiceId()
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

        if ($this->coreParametersHelper->get('custom_object_merge_filter', false)) {
            $subQuery = $this->filterHelper->createMergeFilterQuery($filter, $leadsTableAlias);
        } else {
            $subQuery = $this->filterHelper->createValueQuery(
                $tableAlias,
                $filter
            );

            foreach ($subQuery as $segmentQueryBuilder) {
                $segmentQueryBuilder->andWhere(
                    $segmentQueryBuilder->expr()->eq("{$tableAlias}_contact.contact_id", $leadsTableAlias.'.id')
                );
            }
        }

        switch ($filterOperator) {
            case 'empty':
            case 'neq':
            case 'notLike':
            case '!multiselect':
                $queryBuilder->addLogic(
                    $queryBuilder->expr()->notExists($subQuery->getSQL()),
                    $filter->getGlue()
                );

                break;
            default:
                $queryBuilder->addLogic(
                    $queryBuilder->expr()->exists($subQuery->getSQL()),
                    $filter->getGlue()
                );
        }

        $queryBuilder->setParameters($subQuery->getParameters(), $subQuery->getParameterTypes());

        return $queryBuilder;
    }
}
