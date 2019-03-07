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

use Mautic\LeadBundle\Segment\ContactSegmentFilter;
use Mautic\LeadBundle\Segment\Query\Filter\BaseFilterQueryBuilder;
use Mautic\LeadBundle\Segment\Query\QueryBuilder;
use Mautic\LeadBundle\Segment\RandomParameterName;
use MauticPlugin\CustomObjectsBundle\Exception\InvalidArgumentException;
use MauticPlugin\CustomObjectsBundle\Helper\QueryFilterHelper;

class CustomItemFilterQueryBuilder extends BaseFilterQueryBuilder
{
    /**
     * @var QueryFilterHelper
     */
    private $filterHelper;

    /**
     * @param RandomParameterName $randomParameterNameService
     * @param QueryFilterHelper   $filterHelper
     */
    public function __construct(RandomParameterName $randomParameterNameService, QueryFilterHelper $filterHelper)
    {
        parent::__construct($randomParameterNameService);
        $this->filterHelper = $filterHelper;
    }

    /**
     * @return string
     */
    public static function getServiceId(): string
    {
        return 'mautic.lead.query.builder.custom_item.value';
    }

    public function applyQuery(QueryBuilder $queryBuilder, ContactSegmentFilter $filter): QueryBuilder
    {
        $filterFieldId = $filter->getField();

        $tableAlias = 'cfwq_'.(int) $filter->getField();

        $filterQueryBuilder = $this->filterHelper->createItemNameQueryBuilder(
            $queryBuilder->getConnection(),
            $tableAlias
        );

        $this->filterHelper->addCustomObjectNameExpression(
            $filterQueryBuilder,
            $tableAlias,
            $filter->getOperator(),
            $filter->getParameterValue()
        );

        $filterQueryBuilder->select($tableAlias.'_contact.contact_id as lead_id');
        $filterQueryBuilder->andWhere('l.id = '.$tableAlias.'_contact.contact_id');

        $queryBuilder->setParameter('customFieldId_'.$tableAlias, (int) $filterFieldId);

        switch ($filter->getOperator()) {
            case 'empty':
            case 'neq':
            case 'notLike':
                $queryBuilder->addLogic($queryBuilder->expr()->notExists($filterQueryBuilder->getSQL()), $filter->getGlue());

                break;
            default:
                $queryBuilder->addLogic($queryBuilder->expr()->exists($filterQueryBuilder->getSQL()), $filter->getGlue());
        }

        foreach ($filterQueryBuilder->getParameters() as $paraName => $paraValue) {
            $queryBuilder->setParameter($paraName, $paraValue);
        }

        return $queryBuilder;
    }

    /**
     * @param QueryBuilder         $queryBuilder
     * @param ContactSegmentFilter $filter
     *
     * @return QueryBuilder
     *
     * @throws InvalidArgumentException
     * @throws \Doctrine\DBAL\DBALException
     */
    public function applyQueryOrig(QueryBuilder $queryBuilder, ContactSegmentFilter $filter): QueryBuilder
    {
        $filterOperator   = $filter->getOperator();
        $filterParameters = $filter->getParameterValue();
        $filterFieldId    = $filter->getField();

        $parameters             = $this->getParametersAliases($filterParameters);
        $filterParametersHolder = $filter->getParameterHolder($parameters);
        $tableAlias             = $this->generateRandomParameterName();

        $customQuery = $this->getCustomFieldJoin($queryBuilder, $tableAlias);
        $customQuery->select($tableAlias.'_contact.contact_id as lead_id');
        $queryBuilder->setParameter('customObjectId_'.$tableAlias, (int) $filterFieldId);

        switch ($filterOperator) {
            case 'empty':
                $customQuery->andWhere(
                    $customQuery->expr()->isNotNull($tableAlias.'_item.name')
                );
                $queryBuilder->addLogic($queryBuilder->expr()->notExists($customQuery->getSQL()), $filter->getGlue());

                break;
            case 'notEmpty':
                $customQuery->andWhere(
                    $customQuery->expr()->isNotNull($tableAlias.'_item.name')
                );

                $queryBuilder->addLogic($queryBuilder->expr()->exists($customQuery->getSQL()), $filter->getGlue());

                break;
            case 'notIn':
                $expression = $customQuery->expr()->in(
                    $tableAlias.'_item.name',
                    $filterParametersHolder
                );
                $customQuery->andWhere($expression);
                $queryBuilder->addLogic($queryBuilder->expr()->exists($customQuery->getSQL()), $filter->getGlue());

                break;
            case 'neq':
                $expression = $customQuery->expr()->orX(
                    $customQuery->expr()->eq($tableAlias.'_item.name', $filterParametersHolder),
                    $customQuery->expr()->isNull($tableAlias.'_item.name')
                );

                $customQuery->andWhere($expression);

                $queryBuilder->addLogic($queryBuilder->expr()->notExists($customQuery->getSQL()), $filter->getGlue());

                break;
            case 'notLike':
                $expression = $customQuery->expr()->orX(
                    $customQuery->expr()->isNull($tableAlias.'_item.name'),
                    $customQuery->expr()->like($tableAlias.'_item.name', $filterParametersHolder)
                );

                $customQuery->andWhere($expression);

                $queryBuilder->addLogic($queryBuilder->expr()->notExists($customQuery->getSQL()), $filter->getGlue());

                break;
            default:
                $expression = $customQuery->expr()->{$filterOperator}(
                    $tableAlias.'_item.name',
                    $filterParametersHolder
                );
                $customQuery->andWhere($expression);

                $queryBuilder->addLogic($queryBuilder->expr()->exists($customQuery->getSQL()), $filter->getGlue());
        }

        $queryBuilder->setParametersPairs($filterParametersHolder, $filterParameters);

        return $queryBuilder;
    }

    /**
     * @param string[]|string $filterParameters
     *
     * @return string[]|string
     */
    public function getParametersAliases($filterParameters)
    {
        if (is_array($filterParameters)) {
            return array_map(function () {
                return $this->generateRandomParameterName();
            }, $filterParameters);
        }

        return $this->generateRandomParameterName();
    }

    /**
     * @param QueryBuilder $queryBuilder
     * @param string       $alias
     *
     * @return QueryBuilder
     */
    private function getCustomFieldJoin(QueryBuilder $queryBuilder, string $alias): QueryBuilder
    {
        $customFieldQueryBuilder = $queryBuilder->createQueryBuilder();

        $customFieldQueryBuilder
            ->select(null)
            ->from(MAUTIC_TABLE_PREFIX.'custom_item_xref_contact', $alias.'_contact')
            ->leftJoin(
                $alias.'_contact',
                MAUTIC_TABLE_PREFIX.'custom_item',
                $alias.'_item',
                $alias.'_item.id='.$alias.'_contact.custom_item_id')
            ->where('l.id = '.$alias.'_contact.contact_id');

        $customFieldQueryBuilder->andWhere($customFieldQueryBuilder->expr()->eq($alias.'_item.custom_object_id', ':customObjectId_'.$alias));

        return $customFieldQueryBuilder;
    }
}
