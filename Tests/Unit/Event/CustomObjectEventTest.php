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

use MauticPlugin\CustomObjectsBundle\Entity\CustomObject;
use MauticPlugin\CustomObjectsBundle\Event\CustomObjectEvent;

class CustomObjectEventTest extends \PHPUnit\Framework\TestCase
{
    public function testGettersSetters(): void
    {
        $customObject = $this->createMock(CustomObject::class);
        $event        = new CustomObjectEvent($customObject);

        $this->assertSame($customObject, $event->getCustomObject());
        $this->assertFalse($event->entityIsNew());
    }
}
