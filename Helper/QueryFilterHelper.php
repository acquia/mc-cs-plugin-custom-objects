<?php

declare(strict_types=1);

namespace MauticPlugin\CustomObjectsBundle\Helper;

use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManager;
use Mautic\LeadBundle\Segment\ContactSegmentFilter;
use Mautic\LeadBundle\Segment\Query\Expression\CompositeExpression;
use Mautic\LeadBundle\Segment\Query\QueryBuilder as SegmentQueryBuilder;
use Mautic\LeadBundle\Segment\RandomParameterName;
use MauticPlugin\CustomObjectsBundle\Exception\InvalidArgumentException;
use MauticPlugin\CustomObjectsBundle\Repository\DbalQueryTrait;
use MauticPlugin\CustomObjectsBundle\Segment\Query\UnionQueryContainer;

class QueryFilterHelper
{
    use DbalQueryTrait;

    /**
     * @var EntityManager
     */
    private $entityManager;

    /**
     * @var QueryFilterFactory
     */
    private $queryFilterFactory;

    private RandomParameterName $randomParameterNameService;

    public function __construct(
        EntityManager $entityManager,
        QueryFilterFactory $queryFilterFactory,
        RandomParameterName $randomParameterNameService
    ) {
        $this->entityManager              = $entityManager;
        $this->queryFilterFactory         = $queryFilterFactory;
        $this->randomParameterNameService = $randomParameterNameService;
    }

    public function createValueQuery(
        string $alias,
        ContactSegmentFilter $segmentFilter
    ): UnionQueryContainer {
        $unionQueryContainer = $this->queryFilterFactory->createQuery($alias, $segmentFilter);
        $this->addCustomFieldValueExpressionFromSegmentFilter($unionQueryContainer, $alias, $segmentFilter);

        return $unionQueryContainer;
    }

    public function createItemNameQueryBuilder(string $queryBuilderAlias): SegmentQueryBuilder
    {
        $queryBuilder = new SegmentQueryBuilder($this->entityManager->getConnection());

        return $this->getBasicItemQueryBuilder($queryBuilder, $queryBuilderAlias);
    }

    /**
     * Limit the result to given contact Id, table used is selected by availability
     * CustomFieldValue and CustomItemName are supported.
     *
     * @throws InvalidArgumentException
     */
    public function addContactIdRestriction(SegmentQueryBuilder $queryBuilder, string $queryAlias, int $contactId): void
    {
        if (!$this->hasQueryJoinAlias($queryBuilder, $queryAlias.'_contact')) {
            if (!$this->hasQueryJoinAlias($queryBuilder, $queryAlias.'_value')) {
                throw new InvalidArgumentException('SegmentQueryBuilder contains no usable tables for contact restriction.');
            }
            $tableAlias = $queryAlias.'_contact.contact_id';
        } else {
            $tableAlias = $queryAlias.'_contact.contact_id';
        }
        $queryBuilder->andWhere(
            $queryBuilder->expr()->eq($tableAlias, ':contact_id_'.$contactId)
        );
        $queryBuilder->setParameter('contact_id_'.$contactId, $contactId);
    }

    public function addCustomFieldValueExpressionFromSegmentFilter(
        UnionQueryContainer $unionQueryContainer,
        string $tableAlias,
        ContactSegmentFilter $filter
    ): void {
        foreach ($unionQueryContainer as $segmentQueryBuilder) {
            $valueParameter = $this->randomParameterNameService->generateRandomParameterName();
            $expression     = $this->getCustomValueValueExpression(
                $segmentQueryBuilder,
                $tableAlias,
                $filter->getOperator(),
                $valueParameter
            );

            $this->addOperatorExpression(
                $segmentQueryBuilder,
                $expression,
                $filter->getOperator(),
                $filter->getParameterValue(),
                $valueParameter
            );
        }
    }

    public function addCustomObjectNameExpression(
        SegmentQueryBuilder $queryBuilder,
        string $tableAlias,
        string $operator,
        ?string $value
    ): void {
        $valueParameter = $this->randomParameterNameService->generateRandomParameterName();
        $expression     = $this->getCustomObjectNameExpression($queryBuilder, $tableAlias, $operator, $valueParameter);
        $this->addOperatorExpression($queryBuilder, $expression, $operator, $value, $valueParameter);
    }

    /**
     * @param CompositeExpression|string            $expression
     * @param array|string|CompositeExpression|null $value
     */
    private function addOperatorExpression(
        SegmentQueryBuilder $segmentQueryBuilder,
        $expression,
        string $operator,
        $value,
        string $valueParameter
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
                $valueType      = Connection::PARAM_STR_ARRAY;
                $segmentQueryBuilder->setParameter($valueParameter, $value, $valueType);
                break;
            default:
                $segmentQueryBuilder->setParameter($valueParameter, $value, $valueType);
        }

        switch ($operator) {
            case 'notIn':
                break;
            default:
                $segmentQueryBuilder->andWhere($expression);
                break;
        }
    }

    /**
     * Form the logical expression needed to limit the CustomValue's value for given operator.
     *
     * @return CompositeExpression|string
     */
    private function getCustomValueValueExpression(
        SegmentQueryBuilder $customQuery,
        string $tableAlias,
        string $operator,
        string $valueParameter
    ) {
        switch ($operator) {
            case 'empty':
                $expression = $customQuery->expr()->orX(
                    $customQuery->expr()->isNull($tableAlias.'_value.value'),
                    $customQuery->expr()->eq($tableAlias.'_value.value', $customQuery->expr()->literal(''))
                );

                break;
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
                $expression     = $customQuery->expr()->in(
                    $tableAlias.'_value.value',
                    ":${valueParameter}"
                );

                break;
            case 'neq':
                $expression     = $customQuery->expr()->orX(
                    $customQuery->expr()->neq($tableAlias.'_value.value', ":${valueParameter}"),
                    $customQuery->expr()->isNull($tableAlias.'_value.value')
                );

                break;
            case 'contains':
                $expression = $customQuery->expr()->like($tableAlias.'_value.value', "%:{$valueParameter}%");

                break;
            case 'notLike':
                $expression = $customQuery->expr()->orX(
                    $customQuery->expr()->isNull($tableAlias.'_value.value'),
                    $customQuery->expr()->like($tableAlias.'_value.value', ":${valueParameter}")
                );

                break;
            default:
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
    private function getCustomObjectNameExpression(
        SegmentQueryBuilder $customQuery,
        string $tableAlias,
        string $operator,
        string $valueParameter
    ) {
        switch ($operator) {
            case 'empty':
                $expression = $customQuery->expr()->orX(
                    $customQuery->expr()->isNull($tableAlias.'_item.name'),
                    $customQuery->expr()->eq($tableAlias.'_item.name', $customQuery->expr()->literal(''))
                );

                break;
            case 'notEmpty':
                $expression = $customQuery->expr()->andX(
                    $customQuery->expr()->isNotNull($tableAlias.'_item.name'),
                    $customQuery->expr()->neq($tableAlias.'_item.name', $customQuery->expr()->literal(''))
                );

                break;
            case 'notIn':
            case 'in':
                $expression     = $customQuery->expr()->in(
                    $tableAlias.'_item.name',
                    ":${valueParameter}"
                );

                break;
            case 'neq':
                $expression     = $customQuery->expr()->orX(
                    $customQuery->expr()->eq($tableAlias.'_item.name', ":${valueParameter}"),
                    $customQuery->expr()->isNull($tableAlias.'_item.name')
                );

                break;
            case 'notLike':
                $expression = $customQuery->expr()->orX(
                    $customQuery->expr()->isNull($tableAlias.'_item.name'),
                    $customQuery->expr()->like($tableAlias.'_item.name', ":${valueParameter}")
                );

                break;
            default:
                $expression     = $customQuery->expr()->{$operator}(
                    $tableAlias.'_item.name',
                    ":${valueParameter}"
                );
        }

        return $expression;
    }

    /**
     * Get all tables currently registered in the queryBuilder and check is alias is present.
     */
    private function hasQueryJoinAlias(SegmentQueryBuilder $queryBuilder, $alias): bool
    {
        $joins    = array_column($queryBuilder->getQueryParts()['join'], 0);
        $tables   = array_column($joins, 'joinAlias');
        $tables[] = $queryBuilder->getQueryParts()['from'][0]['alias'];

        return in_array($alias, $tables, true);
    }

    /**
     * Get basic query builder with contact reference and item join.
     */
    private function getBasicItemQueryBuilder(SegmentQueryBuilder $queryBuilder, string $alias): SegmentQueryBuilder
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

    public function createMergeFilterQuery(
        ContactSegmentFilter $segmentFilter,
        string $leadsTableAlias
    ): SegmentQueryBuilder {
        $customItemXrefContactAlias = 'cix';
        $qb                         = new SegmentQueryBuilder($this->entityManager->getConnection());
        $qb->select('1')
           ->from(MAUTIC_TABLE_PREFIX.'custom_item_xref_contact', $customItemXrefContactAlias)
           ->where($qb->expr()->eq($customItemXrefContactAlias.'.contact_id', $leadsTableAlias.'.id'));

        $joinedAlias = [];

        foreach ($segmentFilter->contactSegmentFilterCrate->getMergedProperty() as $filter) {
            $segmentFilterFieldId       = (int) $filter['field'];
            $segmentFilterFieldType     = $filter['type'] ?: $this->queryFilterFactory
                ->getCustomFieldTypeById($segmentFilterFieldId);
            $dataTable                  = $this->queryFilterFactory->getTableNameFromType($segmentFilterFieldType);
            $segmentFilterFieldOperator = (string) $filter['operator'];
            $alias                      = $customItemXrefContactAlias.'_'.$segmentFilterFieldId.'_'.$filter['type'];
            $aliasValue                 = $alias.'_value';
            $isCmoFilter                = $filter['cmo_filter'] ?? false;
            $cinAlias                   = 'cin_'.$segmentFilterFieldId;
            $cinAliasItem               = $cinAlias.'_item';
            $valueParameter             = $this->randomParameterNameService->generateRandomParameterName();

            if ($isCmoFilter && !in_array($cinAliasItem, $joinedAlias, true)) {
                $this->joinMergeCustomItem($qb, $customItemXrefContactAlias, $cinAliasItem, $segmentFilterFieldId);
                $joinedAlias[] = $cinAliasItem;
            } elseif (!in_array($aliasValue, $joinedAlias, true)) {
                $this->joinMergeCustomField(
                    $qb,
                    $customItemXrefContactAlias,
                    $dataTable,
                    $aliasValue,
                    $segmentFilterFieldId
                );
                $joinedAlias[] = $aliasValue;
            }

            $this->addOperatorExpression(
                $qb,
                $this->getMergeExpression(
                    $isCmoFilter,
                    $qb,
                    $cinAlias,
                    $alias,
                    $segmentFilterFieldOperator,
                    $valueParameter
                ),
                $segmentFilterFieldOperator,
                $filter['filter_value'],
                $valueParameter
            );
        }

        return $qb;
    }

    private function joinMergeCustomItem(
        SegmentQueryBuilder $qb,
        string $customItemXrefContactAlias,
        string $cinAliasItem,
        int $segmentFilterFieldId
    ): void {
        $qb->leftJoin(
            $customItemXrefContactAlias,
            MAUTIC_TABLE_PREFIX.'custom_item',
            $cinAliasItem,
            "$customItemXrefContactAlias.custom_item_id = $cinAliasItem.id"
        );
        $qb->andWhere($qb->expr()->eq($cinAliasItem.'.custom_object_id', $segmentFilterFieldId));
    }

    private function joinMergeCustomField(
        SegmentQueryBuilder $qb,
        string $customItemXrefContactAlias,
        string $dataTable,
        string $aliasValue,
        int $segmentFilterFieldId
    ): void {
        $qb->innerJoin(
            $customItemXrefContactAlias,
            MAUTIC_TABLE_PREFIX.$dataTable,
            $aliasValue,
            "$aliasValue.custom_item_id = $customItemXrefContactAlias.custom_item_id AND "
            ."$aliasValue.custom_field_id = $segmentFilterFieldId"
        );
    }

    /**
     * @phpstan-ignore-next-line
     *
     * @return CompositeExpression|string
     */
    private function getMergeExpression(
        bool $isCmoFilter,
        SegmentQueryBuilder $qb,
        string $cinAlias,
        string $alias,
        string $segmentFilterFieldOperator,
        string $valueParameter
    ) {
        if ($isCmoFilter) {
            $expression = $this->getCustomObjectNameExpression(
                $qb,
                $cinAlias,
                $segmentFilterFieldOperator,
                $valueParameter
            );
        } else {
            $expression = $this->getCustomValueValueExpression(
                $qb,
                $alias,
                $segmentFilterFieldOperator,
                $valueParameter
            );
        }

        return $expression;
    }
}
