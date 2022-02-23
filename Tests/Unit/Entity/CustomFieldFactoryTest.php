<?php

declare(strict_types=1);

namespace MauticPlugin\CustomObjectsBundle\Tests\Unit\Entity;

use MauticPlugin\CustomObjectsBundle\Entity\CustomFieldFactory;
use MauticPlugin\CustomObjectsBundle\Entity\CustomObject;
use MauticPlugin\CustomObjectsBundle\Exception\NotFoundException;
use MauticPlugin\CustomObjectsBundle\Provider\CustomFieldTypeProvider;

class CustomFieldFactoryTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var string[]
     */
    private $definedTypes = [
        'text',
        'int',
    ];

    public function testCreate(): void
    {
        $customObject = new CustomObject();

        foreach ($this->definedTypes as $type) {
            $typeClass = ucfirst($type);
            $typeClass = "\MauticPlugin\CustomObjectsBundle\CustomFieldType\\{$typeClass}Type";

            $typeClass = $this->createMock($typeClass);
            $typeClass->expects($this->once())
                ->method('getKey')
                ->willReturn($type);

            $typeProvider = $this->createMock(CustomFieldTypeProvider::class);
            $typeProvider
                ->expects($this->once())
                ->method('getType')
                ->willReturn($typeClass);

            $factory = new CustomFieldFactory($typeProvider);

            $customField = $factory->create($type, $customObject);
            $this->assertSame($type, $customField->getTypeObject()->getKey());
            $this->assertSame($customObject, $customField->getCustomObject());
        }

        $typeProvider = $this->createMock(CustomFieldTypeProvider::class);
        $typeProvider
            ->expects($this->once())
            ->method('getType')
            ->will($this->throwException(new NotFoundException()));

        $factory = new CustomFieldFactory($typeProvider);

        $this->expectException(NotFoundException::class);
        $factory->create('undefined_type', $customObject);
    }
}
