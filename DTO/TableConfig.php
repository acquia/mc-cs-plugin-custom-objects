<?php

declare(strict_types=1);

/*
 * @copyright   2019 Mautic, Inc. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.com
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\CustomObjectsBundle\DTO;

use MauticPlugin\CustomObjectsBundle\Helper\PaginationHelper;

class TableConfig
{
    /**
     * @var int
     */
    private $limit;

    /**
     * @var int
     */
    private $page;

    /**
     * @var string
     */
    private $orderBy;

    /**
     * @var string
     */
    private $orderDirection;

    /**
     * @var array
     */
    private $filters = [];

    /**
     * @param integer $limit
     * @param integer $page
     * @param string  $orderBy
     * @param string  $orderDirection
     */
    public function __construct(int $limit, int $page, string $orderBy, string $orderDirection = 'ASC')
    {
        $this->limit          = $limit;
        $this->page           = $page;
        $this->orderBy        = $orderBy;
        $this->orderDirection = $orderDirection;
    }

    /**
     * @return string
     */
    public function getOrderBy(): string
    {
        return $this->orderBy;
    }

    /**
     * @return string
     */
    public function getOrderDirection(): string
    {
        return $this->orderDirection;
    }

    /**
     * @return integer
     */
    public function getOffset(): int
    {
        $offset = ($this->page === 1) ? 0 : (($this->page - 1) * $this->limit);
        
        return $offset < 0 ? 0 : $offset;
    }

    /**
     * @param string $entityClass
     * @param string $column
     * @param mixed  $value
     * @param string $expression
     */
    public function addFilter(string $entityClass, string $column, $value, $expression = 'eq'): void
    {
        if (!isset($this->filters[$entityClass])) {
            $this->filters[$entityClass] = [];
        }

        $this->filters[$entityClass] = [
            [
                'column' => $column,
                'value'  => $value,
                'expr'   => $expression,
            ],
        ];
    }

    /**
     * @return array
     */
    public function getFilters(): array
    {
        return $this->filters;
    }
}
