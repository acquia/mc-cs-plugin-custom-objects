<?php

declare(strict_types=1);

/*
 * @copyright   2019 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\CustomObjectsBundle\Tests\Unit\Event;

use MauticPlugin\CustomObjectsBundle\Event\CustomFieldTypeEvent;
use MauticPlugin\CustomObjectsBundle\CustomFieldType\CustomFieldTypeInterface;

class CustomFieldTypeEventTest extends \PHPUnit_Framework_TestCase
{
    public function testGettersSetters(): void
    {
        $customFieldType = $this->createMock(CustomFieldTypeInterface::class);
        $event           = new CustomFieldTypeEvent();

        $customFieldType->expects($this->once())
            ->method('getKey')
            ->willReturn('key1');

        $event->addCustomFieldType($customFieldType);

        $this->assertSame(['key1' => $customFieldType], $event->getCustomFieldTypes());
    }
}
