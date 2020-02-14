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

use Doctrine\DBAL\Driver\Statement;
use Doctrine\DBAL\Query\QueryBuilder;

trait DbalQueryTrait
{
    /**
     * Method `execute` returns Statement of int. Ensure it's Statement. (PhpStan was complaining).
     *
     * @throws \UnexpectedValueException
     */
    private function executeSelect(QueryBuilder $queryBuilder): Statement
    {
        $statement = $queryBuilder->execute();

        if ($statement instanceof Statement) {
            return $statement;
        }

        throw new \UnexpectedValueException(sprintf('Unexpected value of %s. Instance of %s expected.', print_r($statement, true), Statement::class));
    }
}
