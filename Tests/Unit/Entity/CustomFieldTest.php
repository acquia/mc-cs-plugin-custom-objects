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

namespace MauticPlugin\CustomObjectsBundle\Tests\Unit\Entity;

use MauticPlugin\CustomObjectsBundle\Entity\CustomField;
use MauticPlugin\CustomObjectsBundle\Entity\CustomObject;
use MauticPlugin\CustomObjectsBundle\CustomFieldType\DateType;
use MauticPlugin\CustomObjectsBundle\Entity\CustomField\Params;
use MauticPlugin\CustomObjectsBundle\Entity\CustomFieldOption;
use MauticPlugin\CustomObjectsBundle\CustomFieldType\SelectType;
use MauticPlugin\CustomObjectsBundle\Exception\UndefinedTransformerException;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Translation\TranslatorInterface;

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

        // Type object defined without transformer
        $typeObject = $this->createMock(DateType::class);
        $typeObject->expects($this->exactly(3))
            ->method('createDefaultValueTransformer')
            ->willThrowException(new UndefinedTransformerException());
        $customField->setTypeObject($typeObject);

        $customField->setTypeObject($typeObject);
        $this->assertNull($customField->getDefaultValue());
        $this->isInstanceOf(Params::class);

        // Set some values
        $customField->setId(55);
        $customField->setLabel('Start Date');
        $customField->setType('date');
        $customField->setCustomObject($customObject);
        $customField->setOrder(4);
        $customField->setRequired(true);
        $customField->setDefaultValue(new \DateTime('2019-04-04'));
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
        $this->assertSame('2019-04-04', $customField->getDefaultValue()->format('Y-m-d'));
        $this->assertSame(['some' => 'param'], $customField->getParams());
    }

    public function testGetFormFieldOptions(): void
    {
        $customField  = new CustomField();
        $typeObject   = new DateType($this->createMock(TranslatorInterface::class));

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
            'required'   => false,
            'label_attr' => [
                'class' => 'control-label',
            ],
        ],
            $customField->getFormFieldOptions(['required' => false])
        );
    }

    public function testGetFormFieldOptionsWithChoices(): void
    {
        $customField  = new CustomField();
        $typeObject   = new SelectType($this->createMock(TranslatorInterface::class));
        $red          = new CustomFieldOption();
        $blue         = new CustomFieldOption();

        $red->setLabel('Red');
        $red->setValue('red');
        $blue->setLabel('Blue');
        $blue->setValue('blue');

        $customField->setTypeObject($typeObject);
        $customField->setLabel('Colors');
        $customField->addOption($red);
        $customField->addOption($blue);

        $this->assertSame([
            'expanded'   => false,
            'multiple'   => false,
            'label'      => 'Colors',
            'required'   => true,
            'label_attr' => ['class' => 'control-label'],
            'attr'       => ['class' => 'form-control'],
            'choices'    => [
                'red'  => 'Red',
                'blue' => 'Blue',
            ],
        ],
            $customField->getFormFieldOptions(['required' => true])
        );
    }

    public function testGetChoices(): void
    {
        $optionA = new CustomFieldOption();
        $optionB = new CustomFieldOption();

        $optionA->setLabel('Option A');
        $optionA->setValue('option_a');
        $optionB->setLabel('Option B');
        $optionB->setValue('option_b');

        $customField = new CustomField();
        $customField->addOption($optionA);
        $customField->addOption($optionB);
        $customField->setTypeObject(new SelectType($this->createMock(TranslatorInterface::class)));

        $this->assertSame([
            'option_a' => 'Option A',
            'option_b' => 'Option B',
        ], $customField->getChoices());
    }

    public function testDefaultValueTransformation()
    {
        $string = 'string';

        $customField = new CustomField();

        // Type object defined without transformer
        $typeObject = $this->createMock(DateType::class);
        $typeObject->expects($this->exactly(5))
            ->method('createDefaultValueTransformer')
            ->willThrowException(new UndefinedTransformerException());
        $customField->setTypeObject($typeObject);

        // NULL
        $this->assertNull($customField->getDefaultValue());

        // String without type object defined
        $customField->setDefaultValue($string);
        $this->assertSame($string, $customField->getDefaultValue());

        $customField->setDefaultValue($string);
        $this->assertSame($string, $customField->getDefaultValue());
        // Type object defined with transformer
        $value            = 'value';
        $transformedValue = 'transformedValue';

        $transformer = $this->createMock(DataTransformerInterface::class);
        $transformer->expects($this->once())
            ->method('transform')
            ->willReturn($transformedValue);
        $transformer->expects($this->once())
            ->method('reverseTransform')
            ->willReturn($value);
        $typeObject = $this->createMock(DateType::class);
        $typeObject->expects($this->exactly(2))
            ->method('createDefaultValueTransformer')
            ->willReturn($transformer);
        $customField->setTypeObject($typeObject);

        $customField->setDefaultValue($value);
        $this->assertSame($transformedValue, $customField->getDefaultValue());
    }
}
