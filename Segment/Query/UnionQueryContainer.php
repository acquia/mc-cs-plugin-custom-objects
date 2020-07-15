<?php

declare(strict_types=1);

/*
 * @copyright   2020 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\CustomObjectsBundle\Segment\Query;

use Doctrine\DBAL\DBALException;
use Mautic\LeadBundle\Segment\Query\QueryBuilder as SegmentQueryBuilder;

/**
 * This is covering impossibility to use union in Doctrine QueryBuilder
 * @see https://github.com/doctrine/orm/issues/5657#issuecomment-181228313
 */
class UnionQueryContainer
{
    /**
     * @var SegmentQueryBuilder[]
     */
    private $queries = [];

    /**
     * @var array
     */
    private $parameters = [];

    /**
     * @var array
     */
    private $parameterTypes = [];

    public function addQuery(SegmentQueryBuilder $queryBuilder): void
    {
        $this->parameters     = array_merge($this->parameters, $queryBuilder->getParameters());
        $this->parameterTypes = array_merge($this->parameterTypes, $queryBuilder->getParameterTypes());
        $this->queries[]      = $queryBuilder;
    }

    /**
     * @throws DBALException
     */
    public function getMergedQueryString(): string
    {
        $queries = [];
        foreach ($this->queries as $query) {
            $queries[] = $query->getSQL();
        }

        return implode(' UNION ALL ', $queries);
    }

    public function getParameters(): array
    {
        return $this->parameters;
    }

    public function getParameterTypes(): array
    {
        return $this->parameterTypes;
    }
}