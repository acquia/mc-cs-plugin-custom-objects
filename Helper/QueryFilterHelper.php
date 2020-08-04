<?php

declare(strict_types=1);

/*
 * @copyright   2019 Mautic Inc. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.com
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\CustomObjectsBundle\Helper;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DBALException;
use Mautic\LeadBundle\Segment\ContactSegmentFilter;
use Mautic\LeadBundle\Segment\Query\Expression\CompositeExpression;
use Mautic\LeadBundle\Segment\Query\QueryBuilder;
use MauticPlugin\CustomObjectsBundle\Exception\InvalidArgumentException;
use MauticPlugin\CustomObjectsBundle\Exception\NotFoundException;
use MauticPlugin\CustomObjectsBundle\Provider\CustomFieldTypeProvider;
use MauticPlugin\CustomObjectsBundle\Repository\DbalQueryTrait;

class QueryFilterHelper
{
    use DbalQueryTrait;

    /**
     * @var CustomFieldTypeProvider
     */
    private $fieldTypeProvider;

    public function __construct(CustomFieldTypeProvider $fieldTypeProvider)
    {
        $this->fieldTypeProvider = $fieldTypeProvider;
    }

    /**
     * @throws NotFoundException
     */
    public function createValueQueryBuilder(
        Connection $connection,
        string $builderAlias,
        int $fieldId,
        ?string $fieldType = null
    ): QueryBuilder {
        $queryBuilder      = new QueryBuilder($connection);
        $fieldType         = $fieldType ?: $this->getCustomFieldType($queryBuilder, $fieldId);
        $queryBuilder      = $this->getBasicItemQueryBuilder($queryBuilder, $builderAlias);
        $this->addCustomFieldValueJoin($queryBuilder, $builderAlias, $fieldType, $fieldId);

        return $queryBuilder;
    }

    public function createItemNameQueryBuilder(Connection $connection, string $queryBuilderAlias): QueryBuilder
    {
        $queryBuilder = new QueryBuilder($connection);

        return $this->getBasicItemQueryBuilder($queryBuilder, $queryBuilderAlias);
    }

    /**
     * Limit the result to given contact Id, table used is selected by availability
     * CustomFieldValue and CustomItemName are supported.
     *
     * @throws InvalidArgumentException
     */
    public function addContactIdRestriction(QueryBuilder $queryBuilder, string $queryAlias, int $contactId): void
    {
        if (!in_array($queryAlias.'_contact', $this->getQueryJoinAliases($queryBuilder), true)) {
            if (!in_array($queryAlias.'_item', $this->getQueryJoinAliases($queryBuilder), true)) {
                throw new InvalidArgumentException('QueryBuilder contains no usable tables for contact restriction.');
            }
            $tableAlias = $queryAlias.'_item.contact_id';
        } else {
            $tableAlias = $queryAlias.'_contact.contact_id';
        }
        $queryBuilder->andWhere(
            $queryBuilder->expr()->eq($tableAlias, ':contact_id_'.$contactId)
        );
        $queryBuilder->setParameter('contact_id_'.$contactId, $contactId);
    }

    public function addCustomFieldValueExpressionFromSegmentFilter(
        QueryBuilder $queryBuilder,
        string $tableAlias,
        ContactSegmentFilter $filter
    ): void {
        $expression = $this->getCustomValueValueExpression($queryBuilder, $tableAlias, $filter->getOperator());

        $this->addOperatorExpression(
            $queryBuilder,
            $tableAlias,
            $expression,
            $filter->getOperator(),
            $filter->getParameterValue()
        );
    }

    public function addCustomObjectNameExpressionFromSegmentFilter(QueryBuilder $queryBuilder, string $tableAlias, ContactSegmentFilter $filter): void
    {
        $expression = $this->getCustomObjectNameExpression($queryBuilder, $tableAlias, $filter->getOperator());
        $this->addOperatorExpression($queryBuilder, $tableAlias, $expression, $filter->getOperator(), $filter->getParameterValue());
    }

    /**
     * Limit the result of query builder to given value of in CustomFieldValue.
     *
     * @param array|string|CompositeExpression $value
     */
    public function addCustomFieldValueExpression(QueryBuilder $queryBuilder, string $tableAlias, string $operator, $value): void
    {
        $expression = $this->getCustomValueValueExpression($queryBuilder, $tableAlias, $operator);
        $this->addOperatorExpression($queryBuilder, $tableAlias, $expression, $operator, $value);
    }

    public function addCustomObjectNameExpression(
        QueryBuilder $queryBuilder,
        string $tableAlias,
        string $operator,
        ?string $value
    ): void {
        $expression = $this->getCustomObjectNameExpression($queryBuilder, $tableAlias, $operator);
        $this->addOperatorExpression($queryBuilder, $tableAlias, $expression, $operator, $value);
    }

    /**
     * @param CompositeExpression|string            $expression
     * @param array|string|CompositeExpression|null $value
     */
    public function addOperatorExpression(
        QueryBuilder $queryBuilder,
        string $tableAlias,
        $expression,
        string $operator,
        $value
    ): void {
        $valueType = null;

        switch ($operator) {
            case 'empty':
            case 'notEmpty':
                break;
            case '!multiselect':
            case 'notIn':
            case 'multiselect':
            case 'in':
                $valueType      = $queryBuilder->getConnection()::PARAM_STR_ARRAY;
                $valueParameter = $tableAlias.'_value_value';

                break;
            default:
                $valueParameter = $tableAlias.'_value_value';
        }

        switch ($operator) {
            case 'notIn':
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
     * Limit the result of queryBuilder to result of customQuery.
     *
     * @throws DBALException
     */
    public function addValueExpressionFromQueryBuilder(
        QueryBuilder $queryBuilder,
        QueryBuilder $customQuery,
        string $glue,
        string $operator
    ): void {
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

    private function getCustomFieldType(QueryBuilder $queryBuilder, int $customFieldId): string
    {
        $qb = $queryBuilder->getConnection()->createQueryBuilder();
        $qb = $qb->select('f.type')
            ->from(MAUTIC_TABLE_PREFIX.'custom_field', 'f')
            ->where($qb->expr()->eq('f.id', $customFieldId));

        $customFieldType = $this->executeSelect($queryBuilder)->fetchColumn();

        return is_string($customFieldType) ? $customFieldType : '';
    }

    /**
     * Form the logical expression needed to limit the CustomValue's value for given operator.
     *
     * @return CompositeExpression|string
     */
    private function getCustomValueValueExpression(QueryBuilder $customQuery, string $tableAlias, string $operator)
    {
        switch ($operator) {
            case 'empty':
            case 'notEmpty':
                $expression = $customQuery->expr()->andX(
                    $customQuery->expr()->isNotNull($tableAlias.'_value.value'),
                    $customQuery->expr()->neq($tableAlias.'_value.value', $customQuery->expr()->literal(''))
                );

                break;
            case 'notIn':
            case '!multiselect':
            case 'in':
            case 'multiselect':
                $valueParameter = $tableAlias.'_value_value';
                $expression     = $customQuery->expr()->in(
                    $tableAlias.'_value.value',
                    ":${valueParameter}"
                );

                break;
            case 'neq':
                $valueParameter = $tableAlias.'_value_value';
                $expression     = $customQuery->expr()->orX(
                    $customQuery->expr()->neq($tableAlias.'_value.value', ":${valueParameter}"),
                    $customQuery->expr()->isNull($tableAlias.'_value.value')
                );

                break;
            case 'contains':
                $valueParameter = $tableAlias.'_value_value';

                $expression = $customQuery->expr()->like($tableAlias.'_value.value', "%:{$valueParameter}%");

                break;
            case 'notLike':
                $valueParameter = $tableAlias.'_value_value';

                $expression = $customQuery->expr()->orX(
                    $customQuery->expr()->isNull($tableAlias.'_value.value'),
                    $customQuery->expr()->like($tableAlias.'_value.value', ":${valueParameter}")
                );

                break;
            default:
                $valueParameter = $tableAlias.'_value_value';
                $expression     = $customQuery->expr()->{$operator}(
                    $tableAlias.'_value.value',
                    ":${valueParameter}"
                );
        }

        return $expression;
    }

    /**
     * Form the logical expression needed to limit the CustomValue's value for given operator.
     *
     * @return CompositeExpression|string
     */
    private function getCustomObjectNameExpression(QueryBuilder $customQuery, string $tableAlias, string $operator)
    {
        switch ($operator) {
            case 'empty':
            case 'notEmpty':
                $expression = $customQuery->expr()->andX(
                    $customQuery->expr()->isNotNull($tableAlias.'_item.name'),
                    $customQuery->expr()->neq($tableAlias.'_item.name', $customQuery->expr()->literal(''))
                );

                break;
            case 'notIn':
            case 'in':
                $valueParameter = $tableAlias.'_value_value';
                $expression     = $customQuery->expr()->in(
                    $tableAlias.'_item.name',
                    ":${valueParameter}"
                );

                break;
            case 'neq':
                $valueParameter = $tableAlias.'_value_value';
                $expression     = $customQuery->expr()->orX(
                    $customQuery->expr()->eq($tableAlias.'_item.name', ":${valueParameter}"),
                    $customQuery->expr()->isNull($tableAlias.'_item.name')
                );

                break;
            case 'notLike':
                $valueParameter = $tableAlias.'_value_value';

                $expression = $customQuery->expr()->orX(
                    $customQuery->expr()->isNull($tableAlias.'_item.name'),
                    $customQuery->expr()->like($tableAlias.'_item.name', ":${valueParameter}")
                );

                break;
            default:
                $valueParameter = $tableAlias.'_value_value';
                $expression     = $customQuery->expr()->{$operator}(
                    $tableAlias.'_item.name',
                    ":${valueParameter}"
                );
        }

        return $expression;
    }

    /**
     * @throws NotFoundException
     */
    private function addCustomFieldValueJoin(
        QueryBuilder $customFieldQueryBuilder,
        string $alias,
        string $fieldType,
        int $fieldId
    ): QueryBuilder {
        $dataTable = $this->fieldTypeProvider->getType($fieldType)->getTableName();

        $customFieldQueryBuilder->leftJoin(
            $alias.'_item',
            $dataTable,
            $alias.'_value',
            $customFieldQueryBuilder->expr()->andX(
                $alias.'_value.custom_item_id = '.$alias.'_item.id',
                $customFieldQueryBuilder->expr()->eq($alias.'_value.custom_field_id', ":{$alias}_custom_field_id")
            )
        );

        $customFieldQueryBuilder->setParameter("{$alias}_custom_field_id", $fieldId);

        return $customFieldQueryBuilder;
    }

    /**
     * Get all tables currently registered in the queryBuilder.
     *
     * @return mixed[]
     */
    private function getQueryJoinAliases(QueryBuilder $queryBuilder): array
    {
        $joins    = array_column($queryBuilder->getQueryParts()['join'], 0);
        $tables   = array_column($joins, 'joinAlias');
        $tables[] = $queryBuilder->getQueryParts()['from'][0]['alias'];

        return $tables;
    }

    /**
     * Get basic query builder with contact reference and item join.
     */
    private function getBasicItemQueryBuilder(QueryBuilder $queryBuilder, string $alias): QueryBuilder
    {
        $customFieldQueryBuilder = $queryBuilder->createQueryBuilder();

        $customFieldQueryBuilder
            ->select('*')
            ->from(MAUTIC_TABLE_PREFIX.'custom_item_xref_contact', $alias.'_contact')
            ->leftJoin(
                $alias.'_contact',
                MAUTIC_TABLE_PREFIX.'custom_item',
                $alias.'_item',
                $alias.'_item.id='.$alias.'_contact.custom_item_id'
            );

        return $customFieldQueryBuilder;
    }
}
