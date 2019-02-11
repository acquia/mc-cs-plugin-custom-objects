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


use Doctrine\DBAL\Connection;
use Mautic\LeadBundle\Segment\Query\QueryBuilder;
use MauticPlugin\CustomObjectsBundle\Exception\InvalidArgumentException;

trait QueryFilterHelper
{
    public function createValueQueryBuilder(Connection $connection, string $queryBuilderAlias, int $customFieldId, $customFieldType = null)
    {
        $queryBuilder      = new QueryBuilder($connection);
        $customFieldType   = $customFieldType ?: $this->getCustomFieldType($queryBuilder, $customFieldId);
        $valueQueryBuilder = $this->getBasicItemQueryBuilder($queryBuilder, $queryBuilderAlias);
        $this->addCustomFieldValueJoin($valueQueryBuilder, $queryBuilderAlias, $customFieldType);
        $this->addCustomFieldIdRestriction($valueQueryBuilder, $queryBuilderAlias, $customFieldId);

        return $valueQueryBuilder;
    }


    public function createItemNameQueryBuilder(Connection $connection, $queryBuilderAlias = null)
    {
        $queryBuilder      = new QueryBuilder($connection);
        $valueQueryBuilder = $this->getBasicItemQueryBuilder($queryBuilder, $queryBuilderAlias);

        return $valueQueryBuilder;
    }

    public function addCustomFieldIdRestriction(QueryBuilder $queryBuilder, string $queryBuilderAlias, $customFieldId)
    {
        $queryBuilder->andWhere($queryBuilder->expr()->eq($queryBuilderAlias . "_value.custom_field_id", ":{$queryBuilderAlias}_custom_field_id"));
        $queryBuilder->setParameter("{$queryBuilderAlias}_custom_field_id", $customFieldId);
    }

    public function addContactIdRestriction(QueryBuilder $queryBuilder, string $queryAlias, int $contactId)
    {
        if (!in_array($queryAlias . '_contact', $this->getQueryJoinAliases($queryBuilder))) {
            if (!in_array($queryAlias . '_item', $this->getQueryJoinAliases($queryBuilder))) {
                throw new InvalidArgumentException('QueryBuilder contains no usable tables for contact restriction.');
            }
            $tableAlias = $queryAlias . '_item.contact_id';
        } else {
            $tableAlias = $queryAlias . '_contact.contact_id';
        }
        $queryBuilder->andWhere(
            $queryBuilder->expr()->eq($tableAlias, ":contact_id_" . $contactId)
        );
        $queryBuilder->setParameter("contact_id_" . $contactId, $contactId);
    }

    private function getQueryJoinAliases(QueryBuilder $queryBuilder): array
    {
        $joins    = array_column($queryBuilder->getQueryParts()['join'], 0);
        $tables   = array_column($joins, 'joinAlias');
        $tables[] = $queryBuilder->getQueryParts()['from'][0]['alias'];

        return $tables;
    }

    public function addCustomFieldValueExpression(QueryBuilder $queryBuilder, string $tableAlias, string $operator, $value)
    {
        $valueType  = null;
        $expression = $this->getCustomValueValueExpression($queryBuilder, $tableAlias, $operator);

        switch ($operator) {
            case 'empty':
            case 'notEmpty':
                break;
            case 'notIn':
                $valueType      = $queryBuilder->getConnection()::PARAM_STR_ARRAY;
                $valueParameter = $tableAlias . '_value_value';
                break;
            case 'in':
                $valueType      = $queryBuilder->getConnection()::PARAM_STR_ARRAY;
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
                $queryBuilder->andWhere($expression);
                break;
            default:
                $queryBuilder->andWhere($expression);
                break;
        }

        if (isset($valueParameter)) {
            $queryBuilder->setParameter($valueParameter, $value, $valueType);
        }
    }

    private function getCustomFieldType(
        \Doctrine\DBAL\Query\QueryBuilder $queryBuilder, $customFieldId
    ): string
    {
        $qb = $queryBuilder->getConnection()->
        createQueryBuilder();

        $customFieldData = $qb->select('f.*')->from(MAUTIC_TABLE_PREFIX . 'custom_field', 'f')->where(
            $qb->expr()->eq('f.id', $customFieldId)
        )->getFirstResult();

        return $customFieldData['type'];
    }

    public function restrictToValueQueryBuilderResult(
        QueryBuilder $queryBuilder,
        QueryBuilder $customQuery,
        $glue,
        $operator
    )
    {
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

    private function getParametersAliases($filterParameters)
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

    private function getCustomValueValueExpression(QueryBuilder $customQuery, $tableAlias, $operator)
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

    private function addCustomFieldValueJoin(QueryBuilder $customFieldQueryBuilder, string $alias, string $fieldType)
    {
        $customFieldQueryBuilder->leftJoin(
            $alias . '_item',
            MAUTIC_TABLE_PREFIX . 'custom_field_value_' . $fieldType,
            $alias . '_value',
            $alias . '_value.custom_item_id = ' . $alias . '_item.id');

        return $customFieldQueryBuilder;
    }

    private function getBasicItemQueryBuilder(QueryBuilder $queryBuilder, string $alias): QueryBuilder
    {
        $customFieldQueryBuilder = $queryBuilder->createQueryBuilder();

        $customFieldQueryBuilder
            ->select("*")
            ->from(MAUTIC_TABLE_PREFIX . 'custom_item_xref_contact', $alias . '_contact')
            ->leftJoin(
                $alias . '_contact',
                MAUTIC_TABLE_PREFIX . 'custom_item',
                $alias . '_item',
                $alias . '_item.id=' . $alias . '_contact.custom_item_id');

        return $customFieldQueryBuilder;
    }
}