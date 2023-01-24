<?php

declare(strict_types=1);

namespace MauticPlugin\CustomObjectsBundle\Tests\Unit\EventListener;

use Doctrine\ORM\Event\LifecycleEventArgs;
use Mautic\LeadBundle\Provider\FilterOperatorProviderInterface;
use MauticPlugin\CustomObjectsBundle\CustomFieldType\TextType;
use MauticPlugin\CustomObjectsBundle\Entity\CustomField;
use MauticPlugin\CustomObjectsBundle\Entity\CustomField\Params;
use MauticPlugin\CustomObjectsBundle\Entity\CustomObject;
use MauticPlugin\CustomObjectsBundle\EventListener\CustomFieldPostLoadSubscriber;
use MauticPlugin\CustomObjectsBundle\Provider\CustomFieldTypeProvider;
use Symfony\Contracts\Translation\TranslatorInterface;

class CustomFieldPostLoadSubscriberTest extends \PHPUnit\Framework\TestCase
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
        $typeObject = new TextType(
            $this->createMock(TranslatorInterface::class),
            $this->createMock(FilterOperatorProviderInterface::class)
        );

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
        $typeObject = new TextType(
            $this->createMock(TranslatorInterface::class),
            $this->createMock(FilterOperatorProviderInterface::class)
        );

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
