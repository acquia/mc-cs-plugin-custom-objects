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

use MauticPlugin\CustomObjectsBundle\Entity\CustomItem;
use MauticPlugin\CustomObjectsBundle\Event\CustomItemEvent;

class CustomItemEventTest extends \PHPUnit\Framework\TestCase
{
    public function testGettersSetters(): void
    {
        $customItem = $this->createMock(CustomItem::class);
        $event      = new CustomItemEvent($customItem);

        $this->assertSame($customItem, $event->getCustomItem());
        $this->assertFalse($event->entityIsNew());
    }
}
