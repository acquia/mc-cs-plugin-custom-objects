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

use Mautic\CoreBundle\Service\FlashBag;
use MauticPlugin\CustomObjectsBundle\Entity\CustomObject;
use MauticPlugin\CustomObjectsBundle\Event\CustomObjectEvent;
use PHPUnit\Framework\TestCase;

class CustomObjectEventTest extends TestCase
{
    /**
     * @var CustomObjectEvent
     */
    private $event;

    /**
     * @var CustomObject
     */
    private $customObject;

    /**
     * @var FlashBag
     */
    private $flashBag;

    protected function setUp(): void
    {
        $this->customObject = new CustomObject();
        $this->event        = new CustomObjectEvent($this->customObject);
        $this->flashBag     = $this->createMock(FlashBag::class);
    }

    public function testGettersSetters(): void
    {
        static::assertSame($this->customObject, $this->event->getCustomObject());
        static::assertFalse($this->event->entityIsNew());
    }

    public function testFlashBagGettersAndSetters(): void
    {
        $this->event->setFlashBag($this->flashBag);
        static::assertSame($this->flashBag, $this->event->getFlashBag());
    }
}
