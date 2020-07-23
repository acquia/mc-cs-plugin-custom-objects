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
use MauticPlugin\CustomObjectsBundle\Segment\Query\UnionQueryContainer;

/**
 * Two subscribers used these private helper methods so this trait provides them from one place to both.
 */
trait QueryBuilderManipulatorTrait
{
    /**
     * @param QueryBuilder|UnionQueryContainer $fromQueryBuilder
     * @param QueryBuilder $toQueryBuilder
     */
    private function copyParams($fromQueryBuilder, QueryBuilder $toQueryBuilder): void
    {
        foreach ($fromQueryBuilder->getParameters() as $key => $value) {
            $paramType = is_array($value) ? $toQueryBuilder->getConnection()::PARAM_STR_ARRAY : null;
            $toQueryBuilder->setParameter($key, $value, $paramType);
        }
    }
}
