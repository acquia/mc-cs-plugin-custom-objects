<?php
declare(strict_types=1);

/*
 * @copyright   2019 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc., Jan Kozak <galvani78@gmail.com
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

/**
 * Class ForeignValueFilterQueryBuilder.
 */
class CustomItemFilterQueryBuilder extends BaseFilterQueryBuilder
{
    /**
     * @return string
     */
    public static function getServiceId()
    {
        return 'mautic.lead.query.builder.custom_item.value';
    }

    /**
     * @param QueryBuilder         $queryBuilder
     * @param ContactSegmentFilter $filter
     *
     * @return QueryBuilder
     * @throws InvalidArgumentException
     * @throws \Doctrine\DBAL\DBALException
     */
    public function applyQuery(QueryBuilder $queryBuilder, ContactSegmentFilter $filter): QueryBuilder
    {
        $filterOperator   = $filter->getOperator();
        $filterParameters = $filter->getParameterValue();
        $filterFieldId    = $filter->getField();

        if (!in_array($type = $filter->getType(), ['int', 'text', 'datetime'], true)) {
            throw new InvalidArgumentException('Invalid object type ' . $filter->getType());
        }

        $parameters             = $this->getParametersAliases($filterParameters);
        $filterParametersHolder = $filter->getParameterHolder($parameters);
        $tableAlias             = $this->generateRandomParameterName();

        $customQuery = $this->getCustomFieldJoin($queryBuilder, $tableAlias);
        $customQuery->select($tableAlias . '_contact.contact_id as lead_id');
        $queryBuilder->setParameter('customObjectId_' . $tableAlias, (int) $filterFieldId);

        switch ($filterOperator) {
            case 'empty':
                $customQuery->andWhere(
                    $customQuery->expr()->isNotNull($tableAlias . '_item.name')
                );
                $queryBuilder->addLogic($queryBuilder->expr()->notExists($customQuery->getSQL()), $filter->getGlue());
                break;
            case 'notEmpty':
                $customQuery->andWhere(
                    $customQuery->expr()->isNotNull($tableAlias . '_item.name')
                );

                $queryBuilder->addLogic($queryBuilder->expr()->exists($customQuery->getSQL()), $filter->getGlue());
                break;
            case 'notIn':
                $expression = $customQuery->expr()->in(
                    $tableAlias . '_item.name',
                    $filterParametersHolder
                );
                $customQuery->andWhere($expression);
                $queryBuilder->addLogic($queryBuilder->expr()->exists($customQuery->getSQL()), $filter->getGlue());
                break;
            case 'neq':
                $expression = $customQuery->expr()->orX(
                    $customQuery->expr()->eq($tableAlias . '_item.name', $filterParametersHolder),
                    $customQuery->expr()->isNull($tableAlias . '_item.name')
                );

                $customQuery->andWhere($expression);

                $queryBuilder->addLogic($queryBuilder->expr()->notExists($customQuery->getSQL()), $filter->getGlue());
                break;
            case 'notLike':
                $expression = $customQuery->expr()->orX(
                    $customQuery->expr()->isNull($tableAlias . '_item.name'),
                    $customQuery->expr()->like($tableAlias . '_item.name', $filterParametersHolder)
                );

                $customQuery->andWhere($expression);

                $queryBuilder->addLogic($queryBuilder->expr()->notExists($customQuery->getSQL()), $filter->getGlue());
                break;
            case 'regexp':
            case 'notRegexp':
                $not        = ($filterOperator === 'notRegexp') ? ' NOT' : '';
                $expression = $tableAlias . '_item.name' . $not . ' REGEXP ' . $filterParametersHolder;

                $customQuery->andWhere($expression);

                $queryBuilder->addLogic($queryBuilder->expr()->exists($customQuery->getSQL()), $filter->getGlue());
                break;
            default:
                $expression = $customQuery->expr()->$filterOperator(
                    $tableAlias . '_item.name',
                    $filterParametersHolder
                );
                $customQuery->andWhere($expression);

                $queryBuilder->addLogic($queryBuilder->expr()->exists($customQuery->getSQL()), $filter->getGlue());
        }

        $queryBuilder->setParametersPairs($filterParametersHolder, $filterParameters);

        return $queryBuilder;
    }

    /**
     * @param $filterParameters
     *
     * @return array|string
     */
    public function getParametersAliases($filterParameters)
    {
        if (is_array($filterParameters)) {
            $parameters = [];
            foreach ($filterParameters as $filterParameter) {
                $parameters[] = $this->generateRandomParameterName();
            }
        } else {
            $parameters = $this->generateRandomParameterName();
        }

        return $parameters;
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
            ->from(MAUTIC_TABLE_PREFIX . 'custom_item_xref_contact', $alias . '_contact')
            ->leftJoin(
                $alias . '_contact',
                MAUTIC_TABLE_PREFIX . 'custom_item',
                $alias . '_item',
                $alias . '_item.id=' . $alias . '_contact.custom_item_id')
            ->where('l.id = ' . $alias . "_contact.contact_id");

        $customFieldQueryBuilder->andWhere($customFieldQueryBuilder->expr()->eq($alias . '_item.custom_object_id', ':customObjectId_' . $alias));

        return $customFieldQueryBuilder;
    }
}
