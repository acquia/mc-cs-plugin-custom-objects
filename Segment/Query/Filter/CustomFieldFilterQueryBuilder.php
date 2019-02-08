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
use MauticPlugin\CustomObjectsBundle\Exception\InvalidArgumentException;

class CustomFieldFilterQueryBuilder extends BaseFilterQueryBuilder
{
    /** {@inheritdoc} */
    public static function getServiceId()
    {
        return 'mautic.lead.query.builder.custom_field.value';
    }

    /**
     * @param QueryBuilder         $queryBuilder
     * @param ContactSegmentFilter $filter
     *
     * @return QueryBuilder
     */
    public function applyQuery(QueryBuilder $queryBuilder, ContactSegmentFilter $filter): QueryBuilder
    {
        $filterOperator   = $filter->getOperator();
        $filterParameters = $filter->getParameterValue();
        $filterFieldId    = $filter->getField();

        $parameters             = $this->getParametersAliases($filterParameters);
        $filterParametersHolder = $filter->getParameterHolder($parameters);
        $tableAlias             = $this->generateRandomParameterName();

        $customQuery = $this->getCustomFieldJoin($queryBuilder, $filter->getType(), $tableAlias);
        $customQuery->select($tableAlias . '_contact.contact_id as lead_id');
        $queryBuilder->setParameter('customFieldId_' . $tableAlias, (int) $filterFieldId);

        switch ($filterOperator) {
            case 'empty':
                $customQuery->andWhere(
                    $customQuery->expr()->isNotNull($tableAlias . '_value.value')
                );
                $queryBuilder->addLogic($queryBuilder->expr()->notExists($customQuery->getSQL()), $filter->getGlue());
                break;
            case 'notEmpty':
                $customQuery->andWhere(
                    $customQuery->expr()->isNotNull($tableAlias . '_value.value')
                );

                $queryBuilder->addLogic($queryBuilder->expr()->exists($customQuery->getSQL()), $filter->getGlue());
                break;
            case 'notIn':
                $expression = $customQuery->expr()->in(
                    $tableAlias . '_value.value',
                    $filterParametersHolder
                );
                $customQuery->andWhere($expression);
                $queryBuilder->addLogic($queryBuilder->expr()->exists($customQuery->getSQL()), $filter->getGlue());
                break;
            case 'neq':
                $expression = $customQuery->expr()->orX(
                    $customQuery->expr()->eq($tableAlias . '_value.value', $filterParametersHolder),
                    $customQuery->expr()->isNull($tableAlias . '_value.value')
                );

                $customQuery->andWhere($expression);

                $queryBuilder->addLogic($queryBuilder->expr()->notExists($customQuery->getSQL()), $filter->getGlue());
                break;
            case 'notLike':
                $expression = $customQuery->expr()->orX(
                    $customQuery->expr()->isNull($tableAlias . '_value.value'),
                    $customQuery->expr()->like($tableAlias . '_value.value', $filterParametersHolder)
                );

                $customQuery->andWhere($expression);

                $queryBuilder->addLogic($queryBuilder->expr()->notExists($customQuery->getSQL()), $filter->getGlue());
                break;
            default:
                $expression = $customQuery->expr()->$filterOperator(
                    $tableAlias . '_value.value',
                    $filterParametersHolder
                );
                $customQuery->andWhere($expression);

                $queryBuilder->addLogic($queryBuilder->expr()->exists($customQuery->getSQL()), $filter->getGlue());
        }

        $queryBuilder->setParametersPairs($filterParametersHolder, $filterParameters);

        return $queryBuilder;
    }
}
