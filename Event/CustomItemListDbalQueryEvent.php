<?php

declare(strict_types=1);

namespace MauticPlugin\CustomObjectsBundle\Event;

use Doctrine\DBAL\Query\QueryBuilder;
use MauticPlugin\CustomObjectsBundle\DTO\TableConfig;
use Symfony\Contracts\EventDispatcher\Event;

class CustomItemListDbalQueryEvent extends Event
{
    public function __construct(private QueryBuilder $queryBuilder, private TableConfig $tableConfig)
    {
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
