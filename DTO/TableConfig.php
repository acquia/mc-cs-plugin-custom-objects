<?php

declare(strict_types=1);

namespace MauticPlugin\CustomObjectsBundle\DTO;

use Doctrine\DBAL\Query\QueryBuilder as DbalQueryBuilder;
use Doctrine\ORM\QueryBuilder as OrmQueryBuilder;
use Mautic\CoreBundle\Helper\ArrayHelper;

class TableConfig
{
    /**
     * @var mixed[]
     */
    private array $parameters = [];

    public function __construct(
        private int $limit,
        private int $page,
        private string $orderBy,
        private string $orderDirection = 'ASC'
    ) {
    }

    public function getOrderBy(): string
    {
        return $this->orderBy;
    }

    public function getOrderDirection(): string
    {
        return $this->orderDirection;
    }

    public function getOffset(): int
    {
        $offset = 1 === $this->page ? 0 : (($this->page - 1) * $this->limit);

        return $offset < 0 ? 0 : $offset;
    }

    public function getLimit(): int
    {
        return $this->limit;
    }

    /**
     * @param mixed $value
     */
    public function addParameter(string $key, $value): void
    {
        $this->parameters[$key] = $value;
    }

    /**
     * @param mixed $defaultValue
     *
     * @return mixed
     */
    public function getParameter(string $key, $defaultValue = null)
    {
        return ArrayHelper::getValue($key, $this->parameters, $defaultValue);
    }

    public function configureOrmQueryBuilder(OrmQueryBuilder $queryBuilder): OrmQueryBuilder
    {
        return $this->configureQueryBuilder($queryBuilder);
    }

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
