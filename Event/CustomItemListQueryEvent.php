<?php

declare(strict_types=1);

namespace MauticPlugin\CustomObjectsBundle\Event;

use Doctrine\ORM\QueryBuilder;
use MauticPlugin\CustomObjectsBundle\DTO\TableConfig;
use Symfony\Contracts\EventDispatcher\Event;

class CustomItemListQueryEvent extends Event
{
    /**
     * @var QueryBuilder
     */
    private $queryBuilder;

    /**
     * @var TableConfig
     */
    private $tableConfig;

    public function __construct(QueryBuilder $queryBuilder, TableConfig $tableConfig)
    {
        $this->queryBuilder = $queryBuilder;
        $this->tableConfig  = $tableConfig;
    }

    public function getQueryBuilder(): QueryBuilder
    {
        return $this->queryBuilder;
    }

    public function getTableConfig(): TableConfig
    {
        return $this->tableConfig;
    }
}
