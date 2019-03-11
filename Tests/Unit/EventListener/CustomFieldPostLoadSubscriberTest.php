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

namespace MauticPlugin\CustomObjectsBundle\Tests\Unit\EventListener;

use MauticPlugin\CustomObjectsBundle\EventListener\CustomFieldPostLoadSubscriber;
use MauticPlugin\CustomObjectsBundle\Provider\CustomFieldTypeProvider;
use Doctrine\ORM\Event\LifecycleEventArgs;
use MauticPlugin\CustomObjectsBundle\Entity\CustomObject;
use MauticPlugin\CustomObjectsBundle\Entity\CustomField;
use MauticPlugin\CustomObjectsBundle\CustomFieldType\TextType;
use MauticPlugin\CustomObjectsBundle\Entity\CustomField\Params;

class CustomFieldPostLoadSubscriberTest extends \PHPUnit_Framework_TestCase
{
    private const FIELD_TYPE = 'text';

    private $customFieldTypeProvider;

    private $customField;

    private $event;

    private $subscriber;

    protected function setUp(): void
    {
        parent::setUp();

        $this->customFieldTypeProvider = $this->createMock(CustomFieldTypeProvider::class);
        $this->customField             = $this->createMock(CustomField::class);
        $this->event                   = $this->createMock(LifecycleEventArgs::class);
        $this->subscriber              = new CustomFieldPostLoadSubscriber($this->customFieldTypeProvider);
    }

    public function testPostLoadWhenNotACustomField(): void
    {
        $this->event->expects($this->once())
            ->method('getObject')
            ->willReturn(new CustomObject());

        $this->customField->expects($this->never())
            ->method('getType');

        $this->customFieldTypeProvider->expects($this->never())
            ->method('getType');

        $this->subscriber->postLoad($this->event);
    }

    public function testPostLoadWhenParamsIsNotAnArray(): void
    {
        $typeObject = new TextType('text');

        $this->event->expects($this->once())
            ->method('getObject')
            ->willReturn($this->customField);

        $this->customField->expects($this->once())
            ->method('getType')
            ->willReturn(self::FIELD_TYPE);

        $this->customFieldTypeProvider->expects($this->once())
            ->method('getType')
            ->with(self::FIELD_TYPE)
            ->willReturn($typeObject);

        $this->customField->expects($this->once())
            ->method('setTypeObject')
            ->with($typeObject);

        $this->customField->expects($this->once())
            ->method('getParams')
            ->willReturn(new Params([]));

        $this->customField->expects($this->never())
            ->method('setParams');

        $this->subscriber->postLoad($this->event);
    }

    public function testPostLoadWhenParamsIsAnArray(): void
    {
        $typeObject = new TextType('text');

        $this->event->expects($this->once())
            ->method('getObject')
            ->willReturn($this->customField);

        $this->customField->expects($this->once())
            ->method('getType')
            ->willReturn(self::FIELD_TYPE);

        $this->customFieldTypeProvider->expects($this->once())
            ->method('getType')
            ->with(self::FIELD_TYPE)
            ->willReturn($typeObject);

        $this->customField->expects($this->once())
            ->method('setTypeObject')
            ->with($typeObject);

        $this->customField->expects($this->exactly(2))
            ->method('getParams')
            ->willReturn([]);

        $this->customField->expects($this->once())
            ->method('setParams')
            ->with($this->isInstanceOf(Params::class));

        $this->subscriber->postLoad($this->event);
    }
}
