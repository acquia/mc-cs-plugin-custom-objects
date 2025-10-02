<?php

declare(strict_types=1);

namespace MauticPlugin\CustomObjectsBundle\Helper;

use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Query\Expression\CompositeExpression;
use Doctrine\ORM\EntityManager;
use Mautic\LeadBundle\Segment\ContactSegmentFilter;
use Mautic\LeadBundle\Segment\Query\QueryBuilder as SegmentQueryBuilder;
use Mautic\LeadBundle\Segment\RandomParameterName;
use MauticPlugin\CustomObjectsBundle\Exception\InvalidArgumentException;
use MauticPlugin\CustomObjectsBundle\Repository\DbalQueryTrait;
use MauticPlugin\CustomObjectsBundle\Segment\Query\UnionQueryContainer;

class QueryFilterHelper
{
    use DbalQueryTrait;

    public function __construct(
        private EntityManager $entityManager,
        private QueryFilterFactory $queryFilterFactory,
        private RandomParameterName $randomParameterNameService
    ) {
    }

    public function createValueQuery(
        string $alias,
        ContactSegmentFilter $segmentFilter,
        bool $filterAlreadyNegated = false
    ): UnionQueryContainer {
        $unionQueryContainer = $this->queryFilterFactory->createQuery($alias, $segmentFilter);
        $this->addCustomFieldValueExpressionFromSegmentFilter($unionQueryContainer, $alias, $segmentFilter, $filterAlreadyNegated);

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
        ContactSegmentFilter $filter,
        bool $filterAlreadyNegated = false
    ): void {
        $filterValue = $filter->getParameterValue();
        foreach ($unionQueryContainer as $segmentQueryBuilder) {
            $valueParameter = $this->randomParameterNameService->generateRandomParameterName();
            $expression     = $this->getCustomValueValueExpression(
                $segmentQueryBuilder,
                $tableAlias,
                $filter,
                $valueParameter,
                $filterAlreadyNegated,
                $filterValue
            );

            $this->addOperatorExpression(
                $segmentQueryBuilder,
                $expression,
                $filter->getOperator(),
                $filterValue,
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
                $valueType      = ArrayParameterType::STRING;
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
     * @param mixed $filterParameterValue
     *
     * @return CompositeExpression|string
     */
    private function getCustomValueValueExpression(
        SegmentQueryBuilder $customQuery,
        string $tableAlias,
        ContactSegmentFilter $filter,
        string $valueParameter,
        bool $alreadyNegated = false,
        $filterParameterValue = null
    ) {
        $operator = $filter->getOperator();
        if ($alreadyNegated) {
            switch ($operator) {
                case 'empty':
                    $operator = 'notEmpty';
                    break;
                case 'neq':
                    $operator = 'eq';
                    break;
                case '!between':
                case 'notBetween':
                    $operator = 'between';
                    break;
            }
        }

        switch ($operator) {
            case 'empty':
                $expression = $customQuery->expr()->orX(
                    $customQuery->expr()->isNull($tableAlias.'_value.value'),
                );
                if ($filter->doesColumnSupportEmptyValue()) {
                    $expression->add(
                        $customQuery->expr()->eq($tableAlias.'_value.value', $customQuery->expr()->literal(''))
                    );
                }
                break;
            case 'notEmpty':
                $expression = $customQuery->expr()->and(
                    $customQuery->expr()->isNotNull($tableAlias.'_value.value'),
                );
                if ($filter->doesColumnSupportEmptyValue()) {
                    $expression->add(
                        $customQuery->expr()->neq($tableAlias.'_value.value', $customQuery->expr()->literal(''))
                    );
                }

                break;
            case 'notIn':
            case '!multiselect':
            case 'in':
            case 'multiselect':
                $expression     = $customQuery->expr()->in(
                    $tableAlias.'_value.value',
                    ":{$valueParameter}"
                );

                break;
            case 'neq':
                $expression     = $customQuery->expr()->or(
                    $customQuery->expr()->neq($tableAlias.'_value.value', ":{$valueParameter}"),
                    $customQuery->expr()->isNull($tableAlias.'_value.value')
                );

                break;
            case 'contains':
                $expression = $customQuery->expr()->like($tableAlias.'_value.value', "%:{$valueParameter}%");

                break;
            case 'notLike':
                $expression = $customQuery->expr()->or(
                    $customQuery->expr()->isNull($tableAlias.'_value.value'),
                    $customQuery->expr()->like($tableAlias.'_value.value', ":{$valueParameter}")
                );

                break;
            case 'between':
            case 'notBetween':
                if (is_array($filterParameterValue)) {
                    $expression = $customQuery->expr()->{$operator}(
                        $tableAlias.'_value.value',
                        array_map(function (mixed $val) use ($customQuery): mixed {
                            return is_numeric($val) && intval($val) === $val ?
                                $val : $customQuery->expr()->literal($val);
                        }, array_values($filterParameterValue))
                    );
                    break;
                }
                // no break
            default:
                $expression     = $customQuery->expr()->{$operator}(
                    $tableAlias.'_value.value',
                    ":{$valueParameter}"
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
        return match ($operator) {
            'empty' => $customQuery->expr()->or(
                $customQuery->expr()->isNull($tableAlias.'_item.name'),
                $customQuery->expr()->eq($tableAlias.'_item.name', $customQuery->expr()->literal(''))
            ),
            'notEmpty' => $customQuery->expr()->and(
                $customQuery->expr()->isNotNull($tableAlias.'_item.name'),
                $customQuery->expr()->neq($tableAlias.'_item.name', $customQuery->expr()->literal(''))
            ),
            'notIn', 'in' => $customQuery->expr()->in(
                $tableAlias.'_item.name',
                ":{$valueParameter}"
            ),
            'neq' => $customQuery->expr()->orX(
                $customQuery->expr()->eq($tableAlias.'_item.name', ':'.$valueParameter),
                $customQuery->expr()->isNull($tableAlias.'_item.name')
            ),
            'notLike' => $customQuery->expr()->or(
                $customQuery->expr()->isNull($tableAlias.'_item.name'),
                $customQuery->expr()->like($tableAlias.'_item.name', ":{$valueParameter}")
            ),
            default => $customQuery->expr()->{$operator}(
                $tableAlias.'_item.name',
                ":{$valueParameter}"
            ),
        };
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
            $segmentMergedFilter        = $segmentFilter;
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
                    $segmentMergedFilter,
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
        ContactSegmentFilter $filter,
        string $valueParameter
    ) {
        $segmentFilterFieldOperator = $filter->getOperator();
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
                $filter,
                $valueParameter,
                false,
                $filter->getParameterValue()
            );
        }

        return $expression;
    }
}
