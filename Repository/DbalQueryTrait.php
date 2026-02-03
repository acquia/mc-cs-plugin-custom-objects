<?php

declare(strict_types=1);

namespace MauticPlugin\CustomObjectsBundle\Repository;

use Doctrine\DBAL\Result;
use Doctrine\DBAL\Query\QueryBuilder;

trait DbalQueryTrait
{
    /**
     * Method `execute` returns Statement of int. Ensure it's Statement. (PhpStan was complaining).
     *
     * @throws \UnexpectedValueException
     */
    private function executeSelect(QueryBuilder $queryBuilder): Result
    {
        $statement = $queryBuilder->executeQuery();

        if ($statement instanceof Result) {
            return $statement;
        }

        throw new \UnexpectedValueException(sprintf('Unexpected value of %s. Instance of %s expected.', print_r($statement, true), Result::class));
    }
}
