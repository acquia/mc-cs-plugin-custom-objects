<?php

declare(strict_types=1);

namespace MauticPlugin\CustomObjectsBundle\Event;

use Doctrine\ORM\QueryBuilder;
use MauticPlugin\CustomObjectsBundle\DTO\TableConfig;
use Symfony\Contracts\EventDispatcher\Event;

class CustomItemListQueryEvent extends Event
{
    public function __construct(
        private QueryBuilder $queryBuilder,
        private TableConfig $tableConfig
    ) {
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
