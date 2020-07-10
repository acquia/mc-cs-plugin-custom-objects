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
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\LeadBundle\Segment\ContactSegmentFilter;
use Mautic\LeadBundle\Segment\Query\Expression\CompositeExpression;
use Mautic\LeadBundle\Segment\Query\QueryBuilder;
use MauticPlugin\CustomObjectsBundle\Exception\InvalidArgumentException;
use MauticPlugin\CustomObjectsBundle\Exception\NotFoundException;
use MauticPlugin\CustomObjectsBundle\Provider\ConfigProvider;
use MauticPlugin\CustomObjectsBundle\Provider\CustomFieldTypeProvider;
use MauticPlugin\CustomObjectsBundle\Repository\DbalQueryTrait;

class QueryFilterHelper
{
    use DbalQueryTrait;

    /**
     * @var CustomFieldTypeProvider
     */
    private $fieldTypeProvider;

    /**
     * @var int
     */
    private $itemRelationLevelLimit;

    public function __construct(CustomFieldTypeProvider $fieldTypeProvider, CoreParametersHelper $coreParametersHelper)
    {
        $this->fieldTypeProvider = $fieldTypeProvider;
        $this->itemRelationLevelLimit = (int) $coreParametersHelper->get(ConfigProvider::CONFIG_PARAM_ITEM_VALUE_TO_CONTACT_RELATION_LIMIT);
    }

    /**
     * @throws NotFoundException
     * @throws DBALException
     */
    public function createValueQueryBuilder(
        Connection $connection,
        string $builderAlias,
        int $fieldId,
        ?string $fieldType = null
    ): QueryBuilder {
        $queryBuilder      = new QueryBuilder($connection);
        $fieldType         = $fieldType ?: $this->getCustomFieldType($queryBuilder, $fieldId);
        $this->getCustomFieldValueJoinQuery($queryBuilder, $builderAlias, $fieldType, $fieldId);

        return $queryBuilder;
    }

    public function createItemNameQueryBuilder(Connection $connection, string $queryBuilderAlias): QueryBuilder
    {
        $queryBuilder = new QueryBuilder($connection);

        // @todo keep this functionality
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
    private function addOperatorExpression(
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
     * @return string Query as string representation because of https://github.com/doctrine/orm/issues/5657#issuecomment-181228313
     * @throws NotFoundException
     * @throws DBALException
     */
    private function getCustomFieldValueJoinQuery(
        QueryBuilder $qb,
        string $alias,
        string $fieldType,
        int $fieldId
    ): string {

        if ($this->itemRelationLevelLimit > 2) {
            // @todo
            throw new \RuntimeException("Relationship level higher than 2 is not implemented yet");
        }

        $dataTable = $this->fieldTypeProvider->getType($fieldType)->getTableName();

        $subSelects = [];

        $subSelects[] = $qb->createQueryBuilder($qb->getConnection())
            ->select('contact_id')
            ->from(MAUTIC_TABLE_PREFIX.$dataTable, "{$alias}_value")
            ->innerJoin(
                "{$alias}_value",
                MAUTIC_TABLE_PREFIX.'custom_item_xref_contact',
                "{$alias}_contact",
                "{$alias}_value.custom_item_id = {$alias}_contact.custom_item_id"
            )
            ->andWhere(
                $qb->expr()->eq('contact_id', 'L.id'),// @todo This should be dynamically added
                $qb->expr()->eq("{$alias}_value.custom_field_id", ":{$alias}_custom_field_id"),
                "{$alias}_value.value < 500" // @todo value parameter and operator
            );

//        if ($this->itemRelationLevelLimit > 1) {
//            // 2nd level
//            $subSelects[] = $customFieldQueryBuilder->createQueryBuilder($customFieldQueryBuilder->getConnection())
//                ->select('custom_item_id_lower')
//                ->from(MAUTIC_TABLE_PREFIX.'custom_item_xref_custom_item')
//                ->where("custom_item_id_higher = {$alias}_item.id")
//                ->getSQL();
//
//            $subSelects[] = $customFieldQueryBuilder->createQueryBuilder($customFieldQueryBuilder->getConnection())
//                ->select('custom_item_id_higher')
//                ->from(MAUTIC_TABLE_PREFIX.'custom_item_xref_custom_item')
//                ->where("custom_item_id_lower = {$alias}_item.id")
//                ->getSQL();
//        }
//
//
//        $customFieldQueryBuilder->innerJoin(
//            $alias.'_item',
//            $dataTable,
//            $alias.'_value',
//            $customItemPart
//        );

        /** @var \Doctrine\DBAL\Query\QueryBuilder $subSelect */
        foreach ($subSelects as $key => $subSelect) {
            // Apply parameter values
            $subSelect->setParameter("{$alias}_custom_field_id", $fieldId);
            // Use string representation - https://github.com/doctrine/orm/issues/5657#issuecomment-181228313
            $subSelects[$key] = $subSelect->getSQL();
        }

        return implode(' UNION ALL ', $subSelects);
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
     * @todo unused, remove me
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
