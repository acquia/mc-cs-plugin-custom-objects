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

use Mautic\CoreBundle\Helper\ArrayHelper;
use Doctrine\ORM\QueryBuilder as OrmQueryBuilder;
use Doctrine\DBAL\Query\QueryBuilder as DbalQueryBuilder;

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
    public function addParameter(string $key, $value): void
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

    /**
     * @param OrmQueryBuilder $queryBuilder
     * 
     * @return OrmQueryBuilder
     */
    public function configureOrmQueryBuilder(OrmQueryBuilder $queryBuilder): OrmQueryBuilder
    {
        return $this->configureQueryBuilder($queryBuilder);
    }

    /**
     * @param DbalQueryBuilder $queryBuilder
     * 
     * @return DbalQueryBuilder
     */
    public function configureDbalQueryBuilder(DbalQueryBuilder $queryBuilder): DbalQueryBuilder
    {
        return $this->configureQueryBuilder($queryBuilder);
    }

    /**
     * Both builders can be configured exactly the same way. Let's do it in one method then.
     * 
     * @param DbalQueryBuilder|OrmQueryBuilder $queryBuilder
     * 
     * @return DbalQueryBuilder|OrmQueryBuilder
     */
    private function configureQueryBuilder($queryBuilder)
    {
        $queryBuilder->setMaxResults($this->getLimit());
        $queryBuilder->setFirstResult($this->getOffset());
        $queryBuilder->orderBy($this->getOrderBy(), $this->getOrderDirection());

        return $queryBuilder;
    }
}
