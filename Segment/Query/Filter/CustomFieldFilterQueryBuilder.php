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
use MauticPlugin\CustomObjectsBundle\Helper\QueryFilterHelper;

class CustomFieldFilterQueryBuilder extends BaseFilterQueryBuilder
{
    use QueryFilterHelper;

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
        $filterFieldId    = $filter->getField();

        $tableAlias        = 'cfwq_' . (int) $filter->getField();


        custom_field.type.provider

        $filterQueryBuilder = $this->createValueQueryBuilder(
            $queryBuilder->getConnection(),
            $tableAlias,
            (int) $filter->getField(),
            $filter->getType()
        );
        $this->addCustomFieldValueExpressionFromSegmentFilter($filterQueryBuilder, $tableAlias, $filter);


        $filterQueryBuilder->select($tableAlias . '_contact.contact_id as lead_id');
        $filterQueryBuilder->andWhere('l.id = ' . $tableAlias . '_contact.contact_id');

        $queryBuilder->setParameter('customFieldId_' . $tableAlias, (int) $filterFieldId);

        switch ($filterOperator) {
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
}
