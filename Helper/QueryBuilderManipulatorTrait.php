<?php

declare(strict_types=1);

/*
 * @copyright   2019 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc
 *
 * @link        https://mautic.com
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\CustomObjectsBundle\Helper;

use Doctrine\DBAL\Query\QueryBuilder;
use Mautic\LeadBundle\Segment\Query\QueryBuilder as SegmentQueryBuilder;

/**
 * Two subscribers used these private helper methods so this trait provides them from one place to both.
 */
trait QueryBuilderManipulatorTrait
{
    /**
     * @param QueryBuilder $fromQueryBuilder
     * @param QueryBuilder $toQueryBuilder
     */
    private function copyParams(QueryBuilder $fromQueryBuilder, QueryBuilder $toQueryBuilder): void
    {
        foreach ($fromQueryBuilder->getParameters() as $key => $value) {
            $paramType = is_array($value) ? $toQueryBuilder->getConnection()::PARAM_STR_ARRAY : null;
            $toQueryBuilder->setParameter($key, $value, $paramType);
        }
    }

    /**
     * Empty and NotEmpty operators require different/opposite behaviour than what segment helper does.
     * We have to handle it ourselves here.
     *
     * @param string              $operator
     * @param string              $queryAlias
     * @param SegmentQueryBuilder $innerQueryBuilder
     */
    private function handleEmptyOperators(string $operator, string $queryAlias, SegmentQueryBuilder $innerQueryBuilder): void
    {
        if ('empty' === $operator) {
            $innerQueryBuilder->resetQueryPart('where');
            $innerQueryBuilder->where(
                $innerQueryBuilder->expr()->orX(
                    $innerQueryBuilder->expr()->isNull($queryAlias.'_value.value'),
                    $innerQueryBuilder->expr()->eq($queryAlias.'_value.value', $innerQueryBuilder->expr()->literal(''))
                )
            );
        }

        if ('!empty' === $operator) {
            $innerQueryBuilder->resetQueryPart('where');
            $innerQueryBuilder->where(
                $innerQueryBuilder->expr()->andX(
                    $innerQueryBuilder->expr()->isNotNull($queryAlias.'_value.value'),
                    $innerQueryBuilder->expr()->neq($queryAlias.'_value.value', $innerQueryBuilder->expr()->literal(''))
                )
            );
        }
    }
}
