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

use MauticPlugin\CustomObjectsBundle\Exception\NotFoundException;
use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\Mapping\ClassMetadata;
use Mautic\CoreBundle\Helper\ArrayHelper;

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
     * @var mixed[]
     */
    private $filters = [];

    /**
     * @var mixed[]
     */
    private $parameters = [];

    /**
     * @param int    $limit
     * @param int    $page
     * @param string $orderBy
     * @param string $orderDirection
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
     * @return int
     */
    public function getOffset(): int
    {
        $offset = 1 === $this->page ? 0 : (($this->page - 1) * $this->limit);

        return $offset < 0 ? 0 : $offset;
    }

    /**
     * @return int
     */
    public function getLimit(): int
    {
        return $this->limit;
    }

    /**
     * @param string $key
     * @param mixed  $value
     */
    public function addParameter(string $key, $value)
    {
        $this->parameters[$key] = $value;
    }

    /**
     * @param string $key
     * @param mixed  $defaultValue
     * 
     * @return mixed
     */
    public function getParameter(string $key, $defaultValue = null)
    {
        return ArrayHelper::getValue($key, $this->parameters, $defaultValue);
    }
}
