<?php

declare(strict_types=1);

/*
 * @copyright   2019 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\CustomObjectsBundle\Repository;

use Doctrine\DBAL\Query\QueryBuilder;
use Doctrine\DBAL\Driver\Statement;

trait DbalQueryTrait
{
    /**
     * Method `execute` returns Statement of int. Ensure it's Statement. (PhpStan was complaining).
     *
     * @param QueryBuilder $queryBuilder
     *
     * @return Statement
     *
     * @throws \UnexpectedValueException
     */
    private function executeSelect(QueryBuilder $queryBuilder): Statement
    {
        $statement = $queryBuilder->execute();

        if ($statement instanceof Statement) {
            return $statement;
        }

        throw new \UnexpectedValueException(
            sprintf('Unexpected value of %s. Instance of %s expected.', print_r($statement, true), Statement::class)
        );
    }
}
