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

namespace MauticPlugin\CustomObjectsBundle\Repository;

use Doctrine\DBAL\Query\QueryBuilder;

trait DbalQueryBuilderParamCopyTrait
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
}
