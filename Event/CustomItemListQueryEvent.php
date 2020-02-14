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

namespace MauticPlugin\CustomObjectsBundle\Event;

use Doctrine\ORM\QueryBuilder;
use MauticPlugin\CustomObjectsBundle\DTO\TableConfig;
use Symfony\Component\EventDispatcher\Event;

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
