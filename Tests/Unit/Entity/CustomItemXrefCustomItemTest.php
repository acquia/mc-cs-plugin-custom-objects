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

namespace MauticPlugin\CustomObjectsBundle\Tests\Unit\Entity;

use MauticPlugin\CustomObjectsBundle\Entity\CustomItem;
use MauticPlugin\CustomObjectsBundle\Entity\CustomItemXrefCustomItem;
use DateTimeImmutable;

class CustomItemXrefCustomItemTest extends \PHPUnit_Framework_TestCase
{
    public function testGetters(): void
    {
        $customItem       = $this->createMock(CustomItem::class);
        $parentCustomItem = $this->createMock(CustomItem::class);
        $dateAdded        = new DateTimeImmutable('2019-03-04 12:34:56');
        $xref             = new CustomItemXrefCustomItem(
            $customItem,
            $parentCustomItem,
            $dateAdded
        );

        $this->assertSame($customItem, $xref->getCustomItem());
        $this->assertSame($parentCustomItem, $xref->getParentCustomItem());
        $this->assertSame($dateAdded, $xref->getDateAdded());
    }
}
