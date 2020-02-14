<?php

declare(strict_types=1);

/*
 * @copyright   2019 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc
 *
 * @link        https://mautic.com
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\CustomObjectsBundle\Tests\Unit\Event;

use Doctrine\ORM\QueryBuilder;
use MauticPlugin\CustomObjectsBundle\DTO\TableConfig;
use MauticPlugin\CustomObjectsBundle\Event\CustomItemListQueryEvent;

class CustomItemListQueryEventTest extends \PHPUnit\Framework\TestCase
{
    public function testGettersSetters(): void
    {
        $queryBuilder  = $this->createMock(QueryBuilder::class);
        $tableConfig   = $this->createMock(TableConfig::class);
        $event         = new CustomItemListQueryEvent($queryBuilder, $tableConfig);

        $this->assertSame($queryBuilder, $event->getQueryBuilder());
        $this->assertSame($tableConfig, $event->getTableConfig());
    }
}
