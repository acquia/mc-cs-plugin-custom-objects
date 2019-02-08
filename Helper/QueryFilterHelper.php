<?php
declare(strict_types=1);

/*
 * @copyright   2019 Mautic Inc. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        http://mautic.com
 * @created     8.2.19
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\CustomObjectsBundle\Helper;


use Mautic\LeadBundle\Segment\Query\QueryBuilder;

trait QueryFilterHelper
{
    public function getCustomValueValueLogicQueryBuilder(
        QueryBuilder $queryBuilder,
        int $customFieldId,
        $glue,
        $type,
        $operator,
        $value,
        $tableAlias): QueryBuilder
    {
        $customQuery = $this->getCustomFieldJoin($queryBuilder, $type, $tableAlias);
        $customQuery->setParameter( $tableAlias. "custom_field_id", $customFieldId);

        $valueType      = null;
        $expression = $this->getCustomValueValueExpression($customQuery, $tableAlias, $operator);

        switch ($operator) {
            case 'empty':
            case 'notEmpty':
                break;
            case 'notIn':
                $valueType = $queryBuilder->getConnection()::PARAM_STR_ARRAY;
                $valueParameter = $tableAlias . '_value_value';
                break;
            case 'in':
                $valueType = $queryBuilder->getConnection()::PARAM_STR_ARRAY;
                $valueParameter = $tableAlias . '_value_value';
                break;
            case 'neq':
                $valueParameter = $tableAlias . '_value_value';
                break;
            case 'notLike':
                $valueParameter = $tableAlias . '_value_value';
                break;
            default:
                $valueParameter = $tableAlias . '_value_value';
        }

        switch ($operator) {
            case 'empty':
            case 'notIn':
                break;
            case 'neq':
            case 'notLike':
                $customQuery->andWhere($expression);
                break;
            default:
                $customQuery->andWhere($expression);
                break;
        }

        if (isset($valueParameter)) {
            $customQuery->setParameter($valueParameter, $value, $valueType);
        }

        return $customQuery;
    }

    public function addCustomValueValueLogic(
        QueryBuilder $queryBuilder,
        int $customFieldId,
        $glue,
        $type,
        $operator,
        $value)
    {
        $tableAlias = 'cqf_' . $customFieldId;

        $customQuery = $this->getCustomValueValueLogicQueryBuilder($queryBuilder, $customFieldId,$glue,$type,$operator,$value, $tableAlias);

        switch ($operator) {
            case 'empty':
            case 'notIn':
            case 'neq':
            case 'notLike':
                $queryBuilder->addLogic($queryBuilder->expr()->notExists($customQuery->getSQL()), $glue);
                break;
            default:
                $queryBuilder->addLogic($queryBuilder->expr()->exists($customQuery->getSQL()), $glue);
                break;
        }
        $queryBuilder->setParametersPairs(array_keys($customQuery->getParameters()), array_values($customQuery->getParameters()));
    }

    /**
     * @param $filterParameters
     *
     * @return array|string
     */
    public
    function getParametersAliases($filterParameters)
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

    public
    function getCustomValueValueExpression(QueryBuilder $customQuery, $tableAlias, $operator)
    {
        switch ($operator) {
            case 'empty':
            case 'notEmpty':
                $expression = $customQuery->expr()->isNotNull($tableAlias . '_value.value');
                break;
            case 'notIn':
                $valueParameter = $tableAlias . '_value_value';
                $expression     = $customQuery->expr()->in(
                    $tableAlias . '_value.value',
                    ":$valueParameter"
                );
                break;
            case 'in':
                $valueParameter = $tableAlias . '_value_value';
                $expression     = $customQuery->expr()->in(
                    $tableAlias . '_value.value',
                    ":$valueParameter"
                );
                break;
            case 'neq':
                $valueParameter = $tableAlias . '_value_value';
                $expression     = $customQuery->expr()->orX(
                    $customQuery->expr()->eq($tableAlias . '_value.value', ":$valueParameter"),
                    $customQuery->expr()->isNull($tableAlias . '_value.value')
                );
                break;
            case 'notLike':
                $valueParameter = $tableAlias . '_value_value';

                $expression = $customQuery->expr()->orX(
                    $customQuery->expr()->isNull($tableAlias . '_value.value'),
                    $customQuery->expr()->like($tableAlias . '_value.value', ":$valueParameter")
                );
                break;
            default:
                $valueParameter = $tableAlias . '_value_value';
                $expression     = $customQuery->expr()->$operator(
                    $tableAlias . '_value.value',
                    ":$valueParameter"
                );

        }

        return $expression;
    }

    /**
     * @param QueryBuilder $queryBuilder
     * @param string       $fieldType
     * @param string|null  $alias
     *
     * @return \Doctrine\DBAL\Query\QueryBuilder
     */
    private
    function getCustomFieldJoin(QueryBuilder $queryBuilder, string $fieldType, string $alias)
    {
        $customFieldQueryBuilder = $queryBuilder->createQueryBuilder();

        $customFieldQueryBuilder
            ->select("*")
            ->from(MAUTIC_TABLE_PREFIX . 'custom_item_xref_contact', $alias . '_contact')
            ->leftJoin(
                $alias . '_contact',
                MAUTIC_TABLE_PREFIX . 'custom_item',
                $alias . '_item',
                $alias . '_item.id=' . $alias . '_contact.custom_item_id')
            ->leftJoin(
                $alias . '_item',
                MAUTIC_TABLE_PREFIX . 'custom_field_value_' . $fieldType,
                $alias . '_value',
                $alias . '_value.custom_item_id = ' . $alias . '_item.id');
        $customFieldQueryBuilder->andWhere(
            $customFieldQueryBuilder->expr()->eq($alias . '_value.custom_field_id', ":{$alias}custom_field_id")
        );

        return $customFieldQueryBuilder;
    }
}