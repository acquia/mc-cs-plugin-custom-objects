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
class UnionQueryContainer implements \Iterator
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

    /**
     * @var int
     */
    private $position = 0;

    /**
     * Whether parameters needs to be rebuild.
     * @var bool
     */
    private $needsRebuild = false;

    public function add(SegmentQueryBuilder $queryBuilder): void
    {
        $this->queries[]    = $queryBuilder;
        $this->needsRebuild = true;
    }

    /**
     * @throws DBALException
     */
    public function getMergedQueryString(): string
    {
        $queries = [];
        foreach ($this->queries as $query) {
            $this->parameters     = array_merge($this->parameters, $query->getParameters());
            $this->parameterTypes = array_merge($this->parameterTypes, $query->getParameterTypes());
            $queries[]            = $query->getSQL();
        }

        $this->needsRebuild = false;

        return implode(' UNION ALL ', $queries);
    }

    /**
     * @throws \RuntimeException
     */
    public function getParameters(): array
    {
        $this->checkRebuildStatus();
        return $this->parameters;
    }

    /**
     * @throws \RuntimeException
     */
    public function getParameterTypes(): array
    {
        $this->checkRebuildStatus();
        return $this->parameterTypes;
    }

    // Iterator methods

    public function current(): SegmentQueryBuilder
    {
        return $this->queries[$this->position];
    }

    public function next(): void
    {
        ++$this->position;
    }

    public function key(): int
    {
        return $this->position;
    }

    public function valid(): bool
    {
        return isset($this->queries[$this->position]);
    }

    public function rewind(): void
    {
        $this->position = 0;
    }

    /**
     * This checks development workflow to prevent bugs
     * @throws \RuntimeException
     */
    private function checkRebuildStatus(): void
    {
        if ($this->needsRebuild) {
            throw new \RuntimeException('Use getMergedQueryString() method at first to rebuild parameters and types');
        }
    }
}