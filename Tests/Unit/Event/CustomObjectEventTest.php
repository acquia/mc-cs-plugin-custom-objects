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

    protected function setUp(): void
    {
        $this->customObject = new CustomObject();
        $this->event        = new CustomObjectEvent($this->customObject);
    }

    public function testGettersSetters(): void
    {
        static::assertSame($this->customObject, $this->event->getCustomObject());
        static::assertFalse($this->event->entityIsNew());
    }

    public function testMessageGettersAndSetters(): void
    {
        $message = 'Some message';
        $this->event->setMessage($message);
        static::assertSame($message, $this->event->getMessage());
    }
}
