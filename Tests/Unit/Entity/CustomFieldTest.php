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

namespace MauticPlugin\CustomObjectsBundle\Tests\Unit\Entity;

use MauticPlugin\CustomObjectsBundle\Entity\CustomField;
use MauticPlugin\CustomObjectsBundle\Entity\CustomObject;
use MauticPlugin\CustomObjectsBundle\CustomFieldType\DateType;
use MauticPlugin\CustomObjectsBundle\Entity\CustomField\Params;

class CustomFieldTest extends \PHPUnit_Framework_TestCase
{
    public function testToString(): void
    {
        $customField = new CustomField();
        $customField->setLabel('Start Date');

        $this->assertSame('Start Date', (string) $customField);
        $this->assertSame('Start Date', $customField->__toString());
    }

    public function testToArray(): void
    {
        $customObject = $this->createMock(CustomObject::class);
        $customObject->method('getId')->willReturn(34);

        $customField = new CustomField();
        $customField->setLabel('Start Date');
        $customField->setType('date');
        $customField->setCustomObject($customObject);
        $customField->setOrder(4);

        $this->assertSame([
            'id'           => null,
            'label'        => 'Start Date',
            'type'         => 'date',
            'customObject' => 34,
            'order'        => 4,
        ], $customField->toArray());
    }

    public function testGettersSetters(): void
    {
        $customObject = new CustomObject();
        $typeObject   = new DateType('date');
        $customField  = new CustomField();

        // Test Initial values
        $this->assertNull($customField->getId());
        $this->assertNull($customField->getLabel());
        $this->assertNull($customField->getName());
        $this->assertNull($customField->getType());
        $this->assertNull($customField->getTypeObject());
        $this->assertNull($customField->getCustomObject());
        $this->assertNull($customField->getOrder());
        $this->assertFalse($customField->isRequired());
        $this->assertNull($customField->getDefaultValue());
        $this->isInstanceOf(Params::class);

        // Set some values
        $customField->setId(55);
        $customField->setLabel('Start Date');
        $customField->setType('date');
        $customField->setTypeObject($typeObject);
        $customField->setCustomObject($customObject);
        $customField->setOrder(4);
        $customField->setRequired(true);
        $customField->setDefaultValue('2019-04-04');
        $customField->setParams(['some' => 'param']);

        // Test new values
        $this->assertSame(55, $customField->getId());
        $this->assertSame('Start Date', $customField->getLabel());
        $this->assertSame('Start Date', $customField->getName());
        $this->assertSame('date', $customField->getType());
        $this->assertSame($typeObject, $customField->getTypeObject());
        $this->assertSame($customObject, $customField->getCustomObject());
        $this->assertSame(4, $customField->getOrder());
        $this->assertTrue($customField->isRequired());
        $this->assertSame('2019-04-04', $customField->getDefaultValue());
        $this->assertSame(['some' => 'param'], $customField->getParams());
    }

    public function testGetFormFieldOptions(): void
    {
        $customField  = new CustomField();
        $typeObject   = new DateType('date');

        $customField->setTypeObject($typeObject);
        $customField->setLabel('Start Date');
        $customField->setRequired(true);

        $this->assertSame([
            'widget' => 'single_text',
            'format' => 'yyyy-MM-dd',
            'attr'   => [
                'data-toggle' => 'date',
                'class'       => 'form-control',
            ],
            'label'      => 'Start Date',
            'required'   => true,
            'label_attr' => [
                'class' => 'control-label',
            ],
        ], $customField->getFormFieldOptions());
    }
}
