<?php

/*
 * @copyright   2019 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\CustomObjectsBundle\Tests\Entity;

use MauticPlugin\CustomObjectsBundle\Entity\CustomFieldFactory;
use MauticPlugin\CustomObjectsBundle\Exception\NotFoundException;
use MauticPlugin\CustomObjectsBundle\Provider\CustomFieldTypeProvider;

class CustomFieldFactoryTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var array
     */
    var $definedTypes = [
            'text',
            'int',
        ];

    public function testCreate()
    {
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

            $customField = $factory->create($type);
            $this->assertSame($type, $customField->getType()->getKey());
        }

        $typeProvider = $this->createMock(CustomFieldTypeProvider::class);
        $typeProvider
            ->expects($this->once())
            ->method('getType')
            ->will($this->throwException(new NotFoundException()));

        $factory = new CustomFieldFactory($typeProvider);

        $this->expectException(\InvalidArgumentException::class);
        $factory->create('undefined_type');
    }
}
