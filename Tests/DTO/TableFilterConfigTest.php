<?php

declare(strict_types=1);

/*
 * @copyright   2019 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\CustomObjectsBundle\Tests\DTO;

use MauticPlugin\CustomObjectsBundle\DTO\TableFilterConfig;
use MauticPlugin\CustomObjectsBundle\Entity\CustomObject;

class TableFilterConfigTest extends \PHPUnit_Framework_TestCase
{
    public function testGetters(): void
    {
        $filter = new TableFilterConfig(CustomObject::class, 'columnA', 'value A');

        $this->assertSame('value A', $filter->getValue());
        $this->assertSame(CustomObject::class, $filter->getEntityName());
        $this->assertSame('columnA', $filter->getColumnName());
        $this->assertSame('CustomObject.columnA', $filter->getFullColumnName());
        $this->assertSame('eq', $filter->getExpression());
        $this->assertSame('CustomObject', $filter->getTableAlias());
    }
}
