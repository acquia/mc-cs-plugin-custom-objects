<?php

declare(strict_types=1);

namespace MauticPlugin\CustomObjectsBundle\Helper;

use Doctrine\DBAL\Query\QueryBuilder;
use MauticPlugin\CustomObjectsBundle\Segment\Query\UnionQueryContainer;

/**
 * Two subscribers used these private helper methods so this trait provides them from one place to both.
 */
trait QueryBuilderManipulatorTrait
{
    /**
     * @param QueryBuilder|UnionQueryContainer $fromQueryBuilder
     */
    private function copyParams($fromQueryBuilder, QueryBuilder $toQueryBuilder): void
    {
        foreach ($fromQueryBuilder->getParameters() as $key => $value) {
            $paramType = is_array($value) ? $toQueryBuilder->getConnection()::PARAM_STR_ARRAY : null;
            $toQueryBuilder->setParameter($key, $value, $paramType);
        }
    }
}
