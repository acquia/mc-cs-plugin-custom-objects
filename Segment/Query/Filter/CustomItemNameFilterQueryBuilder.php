<?php

declare(strict_types=1);

namespace MauticPlugin\CustomObjectsBundle\Segment\Query\Filter;

use Doctrine\DBAL\DBALException;
use Mautic\LeadBundle\Segment\ContactSegmentFilter;
use Mautic\LeadBundle\Segment\Query\Filter\BaseFilterQueryBuilder;
use Mautic\LeadBundle\Segment\Query\QueryBuilder;
use Mautic\LeadBundle\Segment\RandomParameterName;
use MauticPlugin\CustomObjectsBundle\Helper\QueryBuilderManipulatorTrait;
use MauticPlugin\CustomObjectsBundle\Helper\QueryFilterHelper;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class CustomItemNameFilterQueryBuilder extends BaseFilterQueryBuilder
{
    use QueryBuilderManipulatorTrait;

    /**
     * @var QueryFilterHelper
     */
    private $filterHelper;

    public function __construct(
        RandomParameterName $randomParameterNameService,
        QueryFilterHelper $filterHelper,
        EventDispatcherInterface $dispatcher
    ) {
        parent::__construct($randomParameterNameService, $dispatcher);
        $this->filterHelper = $filterHelper;
    }

    public static function getServiceId(): string
    {
        return 'mautic.lead.query.builder.custom_item.value';
    }

    /**
     * @throws DBALException
     */
    public function applyQuery(QueryBuilder $queryBuilder, ContactSegmentFilter $filter): QueryBuilder
    {
        $leadsTableAlias = $queryBuilder->getTableAlias(MAUTIC_TABLE_PREFIX.'leads');
        $customObjectId  = $filter->getField();

        $tableAlias = 'cin_'.(int) $filter->getField();

        $filterQueryBuilder = $this->filterHelper->createItemNameQueryBuilder($tableAlias);

        $filterQueryBuilder->andWhere(
            $filterQueryBuilder->expr()->eq($tableAlias.'_item.custom_object_id', ':'.$tableAlias.'ObjectId')
        );

        $filterQueryBuilder->setParameter($tableAlias.'ObjectId', (int) $customObjectId);

        $this->filterHelper->addCustomObjectNameExpression(
            $filterQueryBuilder,
            $tableAlias,
            $filter->getOperator(),
            (string) $filter->getParameterValue()
        );

        $filterQueryBuilder->select($tableAlias.'_contact.contact_id as lead_id');
        $filterQueryBuilder->andWhere($leadsTableAlias.'.id = '.$tableAlias.'_contact.contact_id');

        switch ($filter->getOperator()) {
            case 'empty':
            case 'neq':
            case 'notLike':
                $queryBuilder->addLogic($queryBuilder->expr()->notExists($filterQueryBuilder->getSQL()), $filter->getGlue());

                break;
            default:
                $queryBuilder->addLogic($queryBuilder->expr()->exists($filterQueryBuilder->getSQL()), $filter->getGlue());
        }

        $this->copyParams($filterQueryBuilder, $queryBuilder);

        return $queryBuilder;
    }
}
