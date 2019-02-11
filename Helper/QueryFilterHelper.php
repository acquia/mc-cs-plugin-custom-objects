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
    /**
     * @param Connection  $connection
     * @param string      $queryBuilderAlias
     * @param int         $customFieldId
     * @param string|null $customFieldType
     *
     * @return QueryBuilder
     */
    public function createValueQueryBuilder(
        Connection $connection,
        string $queryBuilderAlias,
        int $customFieldId,
        ?string $customFieldType = null
    )
    {
        $queryBuilder      = new QueryBuilder($connection);
        $customFieldType   = $customFieldType ?: $this->getCustomFieldType($queryBuilder, $customFieldId);
        $valueQueryBuilder = $this->getBasicItemQueryBuilder($queryBuilder, $queryBuilderAlias);
        $this->addCustomFieldValueJoin($valueQueryBuilder, $queryBuilderAlias, $customFieldType);
        $this->addCustomFieldIdRestriction($valueQueryBuilder, $queryBuilderAlias, $customFieldId);

        return $valueQueryBuilder;
    }

    /**
     * @param Connection $connection
     * @param null       $queryBuilderAlias
     *
     * @return QueryBuilder
     */
    public function createItemNameQueryBuilder(Connection $connection, $queryBuilderAlias = null)
    {
        $queryBuilder      = new QueryBuilder($connection);
        $valueQueryBuilder = $this->getBasicItemQueryBuilder($queryBuilder, $queryBuilderAlias);

        return $valueQueryBuilder;
    }

    /**
     * Restricts the result to certain custom field
     *
     * @param QueryBuilder $queryBuilder
     * @param string       $queryBuilderAlias
     * @param              $customFieldId
     */
    public function addCustomFieldIdRestriction(QueryBuilder $queryBuilder, string $queryBuilderAlias, $customFieldId)
    {
        $queryBuilder->andWhere($queryBuilder->expr()->eq($queryBuilderAlias . "_value.custom_field_id", ":{$queryBuilderAlias}_custom_field_id"));
        $queryBuilder->setParameter("{$queryBuilderAlias}_custom_field_id", $customFieldId);
    }

    /**
     * Limit the result to given contact Id, table used is selected by availability
     * CustomFieldValue and CustomItemName are supported
     *
     * @param QueryBuilder $queryBuilder
     * @param string       $queryAlias
     * @param int          $contactId
     *
     * @throws InvalidArgumentException
     */
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

    /**
     * Limit the result of query builder to given value of in CustomFieldValue
     *
     * @param QueryBuilder $queryBuilder
     * @param string       $tableAlias
     * @param string       $operator
     * @param              $value
     */
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

    /**
     * @param QueryBuilder $queryBuilder
     * @param int          $customFieldId
     *
     * @return string
     */
    private function getCustomFieldType(QueryBuilder $queryBuilder, int $customFieldId): string
    {
        $qb = $queryBuilder->getConnection()->
        createQueryBuilder();

        $customFieldData = $qb->select('f.*')->from(MAUTIC_TABLE_PREFIX . 'custom_field', 'f')->where(
            $qb->expr()->eq('f.id', $customFieldId)
        )->getFirstResult();

        return $customFieldData['type'];
    }

    /**
     * Limit the result of queryBuilder to result of customQuery
     *
     * @param QueryBuilder $queryBuilder
     * @param QueryBuilder $customQuery
     * @param              $glue
     * @param              $operator
     *
     * @throws \Doctrine\DBAL\DBALException
     */
    public function addValueExpressionFromQueryBuilder(
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

    /**
     * Form the logical expression needed to limit the CustomValue's value for given operator
     *
     * @param QueryBuilder $customQuery
     * @param              $tableAlias
     * @param              $operator
     *
     * @return \Mautic\LeadBundle\Segment\Query\Expression\CompositeExpression|string
     */
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

    /**
     * Join CustomFieldValue to CustomItem
     *
     * @param QueryBuilder $customFieldQueryBuilder
     * @param string       $alias
     * @param string       $fieldType
     *
     * @return QueryBuilder
     */
    private function addCustomFieldValueJoin(QueryBuilder $customFieldQueryBuilder, string $alias, string $fieldType)
    {
        $customFieldQueryBuilder->leftJoin(
            $alias . '_item',
            MAUTIC_TABLE_PREFIX . 'custom_field_value_' . $fieldType,
            $alias . '_value',
            $alias . '_value.custom_item_id = ' . $alias . '_item.id');

        return $customFieldQueryBuilder;
    }

    /**
     * Get all tables currently registered in the queryBuilder
     *
     * @param QueryBuilder $queryBuilder
     *
     * @return array
     */
    private function getQueryJoinAliases(QueryBuilder $queryBuilder): array
    {
        $joins    = array_column($queryBuilder->getQueryParts()['join'], 0);
        $tables   = array_column($joins, 'joinAlias');
        $tables[] = $queryBuilder->getQueryParts()['from'][0]['alias'];

        return $tables;
    }

    /**
     * Get basic query builder with contact reference and item join
     *
     * @param QueryBuilder $queryBuilder
     * @param string       $alias
     *
     * @return QueryBuilder
     */
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