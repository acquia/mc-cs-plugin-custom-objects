<?php

declare(strict_types=1);

namespace MauticPlugin\CustomObjectsBundle\Tests\Unit\Entity;

use Mautic\LeadBundle\Provider\FilterOperatorProviderInterface;
use MauticPlugin\CustomObjectsBundle\CustomFieldType\CheckboxGroupType;
use MauticPlugin\CustomObjectsBundle\CustomFieldType\CountryType;
use MauticPlugin\CustomObjectsBundle\CustomFieldType\DateType;
use MauticPlugin\CustomObjectsBundle\CustomFieldType\EmailType;
use MauticPlugin\CustomObjectsBundle\CustomFieldType\SelectType;
use MauticPlugin\CustomObjectsBundle\Entity\CustomField;
use MauticPlugin\CustomObjectsBundle\Entity\CustomField\Params;
use MauticPlugin\CustomObjectsBundle\Entity\CustomFieldOption;
use MauticPlugin\CustomObjectsBundle\Entity\CustomObject;
use MauticPlugin\CustomObjectsBundle\Exception\NotFoundException;
use MauticPlugin\CustomObjectsBundle\Exception\UndefinedTransformerException;
use MauticPlugin\CustomObjectsBundle\Form\Validator\Constraints\AllowUniqueIdentifier;
use MauticPlugin\CustomObjectsBundle\Helper\CsvHelper;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Symfony\Component\Validator\Constraints\Callback;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Validator\Mapping\ClassMetadata;
use Symfony\Component\Validator\Violation\ConstraintViolationBuilderInterface;

class CustomFieldTest extends \PHPUnit\Framework\TestCase
{
    public function testClone(): void
    {
        $customField = new CustomField();
        $customField->setAlias('field-a');

        $clone = clone $customField;

        $this->assertNull($clone->getAlias());
    }

    public function testLoadValidatorMetadata(): void
    {
        $metadata = $this->createMock(ClassMetadata::class);
        $object   = new CustomField();

        $metadata->expects($this->exactly(7))
            ->method('addPropertyConstraint')
            ->withConsecutive(
                ['label', $this->isInstanceOf(NotBlank::class)],
                ['label', $this->isInstanceOf(Length::class)],
                ['alias', $this->isInstanceOf(Length::class)],
                ['type', $this->isInstanceOf(NotBlank::class)],
                ['type', $this->isInstanceOf(Length::class)],
                ['customObject', $this->isInstanceOf(NotBlank::class)],
                ['defaultValue', $this->isInstanceOf(Length::class)]
            );

        $metadata->expects($this->exactly(2))
            ->method('addConstraint')
            ->withConsecutive(
                [$this->isInstanceOf(Callback::class)],
                [$this->isInstanceOf(AllowUniqueIdentifier::class)]
            );

        $object->loadValidatorMetadata($metadata);
    }

    public function testValidateValueWhenValid(): void
    {
        $context     = $this->createMock(ExecutionContextInterface::class);
        $translator  = $this->createMock(TranslatorInterface::class);
        $provider    = $this->createMock(FilterOperatorProviderInterface::class);
        $customField = new CustomField();

        $context->expects($this->never())
            ->method('buildViolation');

        $customField->setTypeObject(new EmailType($translator, $provider));
        $customField->setDefaultValue('valid@email.address');
        $customField->validateDefaultValue($context);
    }

    public function testValidateValueWhenInvalid(): void
    {
        $context     = $this->createMock(ExecutionContextInterface::class);
        $translator  = $this->createMock(TranslatorInterface::class);
        $violation   = $this->createMock(ConstraintViolationBuilderInterface::class);
        $provider    = $this->createMock(FilterOperatorProviderInterface::class);
        $customField = new CustomField();

        $translator->method('trans')->willReturn('a validation message');

        $context->expects($this->once())
            ->method('buildViolation')
            ->with('a validation message')
            ->willReturn($violation);

        $violation->expects($this->once())
            ->method('addViolation');

        $customField->setTypeObject(new EmailType($translator, $provider));
        $customField->setDefaultValue('invalid.email.address');
        $customField->validateDefaultValue($context);
    }

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
        $this->assertFalse($customField->getIsUniqueIdentifier());

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
        $customField->setIsUniqueIdentifier(true);
        $customField->setDefaultValue(new \DateTime('2019-04-04'));
        $customField->setParams(['placeholder' => 'param']);

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
        $this->assertInstanceOf(Params::class, $customField->getParams());
        $this->assertSame('param', $customField->getParams()->getPlaceholder());
        $this->assertTrue($customField->getIsUniqueIdentifier());
    }

    public function testGetFormFieldOptions(): void
    {
        $customField  = new CustomField();
        $typeObject   = new DateType(
            $this->createMock(TranslatorInterface::class),
            $this->createMock(FilterOperatorProviderInterface::class)
        );

        $customField->setTypeObject($typeObject);
        $customField->setLabel('Start Date');
        $customField->setRequired(true);

        $this->assertSame(
            [
                'widget' => 'single_text',
                'format' => 'yyyy-MM-dd',
                'html5'  => false,
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
        $red          = new CustomFieldOption();
        $blue         = new CustomFieldOption();
        $typeObject   = new SelectType(
            $this->createMock(TranslatorInterface::class),
            $this->createMock(FilterOperatorProviderInterface::class)
        );

        $red->setLabel('Red label');
        $red->setValue('red value');
        $blue->setLabel('Blue label');
        $blue->setValue('blue value');

        $customField->setTypeObject($typeObject);
        $customField->setLabel('Colors');
        $customField->addOption($red);
        $customField->addOption($blue);
        $customField->setParams(['placeholder' => 'Select one']);

        $expectedOptions = [
            'expanded'   => false,
            'multiple'   => false,
            'label'      => 'Colors',
            'required'   => true,
            'label_attr' => ['class' => 'control-label'],
            'attr'       => [
                'class'            => 'form-control',
                'data-placeholder' => 'Select one',
            ],
            'choices'    => [
                'Red label'  => 'red value',
                'Blue label' => 'blue value',
            ],
        ];

        $this->assertSame($expectedOptions, $customField->getFormFieldOptions(['required' => true]));

        $params = new Params(['placeholder' => 'Select one']);
        $customField->setParams($params);

        $this->assertSame($expectedOptions, $customField->getFormFieldOptions(['required' => true]));
    }

    public function testGetChoices(): void
    {
        $optionA = new CustomFieldOption();
        $optionB = new CustomFieldOption();

        $optionA->setLabel('Option A label');
        $optionA->setValue('option_a_value');
        $optionB->setLabel('Option B label');
        $optionB->setValue('option_b_value');

        $customField = new CustomField();
        $customField->addOption($optionA);
        $customField->addOption($optionB);
        $customField->setTypeObject(
            new SelectType(
                $this->createMock(TranslatorInterface::class),
                $this->createMock(FilterOperatorProviderInterface::class)
            )
        );

        $this->assertSame([
             'Option A label' => 'option_a_value',
             'Option B label' => 'option_b_value',
        ], $customField->getChoices());
    }

    public function testGetChoicesWithCountryField(): void
    {
        $customField = new CustomField();
        $customField->setTypeObject(
            new CountryType(
                $this->createMock(TranslatorInterface::class),
                $this->createMock(FilterOperatorProviderInterface::class)
            )
        );

        $this->assertSame('Czech Republic', $customField->getChoices()['Czech Republic']);
    }

    public function testValueToLabel(): void
    {
        $customField = new CustomField();
        $optionB     = new CustomFieldOption();
        $optionB->setLabel('Option B');
        $optionB->setValue('option_b');
        $customField->addOption($optionB);
        $customField->setTypeObject(
            new SelectType(
                $this->createMock(TranslatorInterface::class),
                $this->createMock(FilterOperatorProviderInterface::class)
            )
        );
        $this->assertSame('Option B', $customField->valueToLabel('option_b'));
    }

    public function testValueToLabelIfOptionNotFound(): void
    {
        $customField = new CustomField();
        $customField->setTypeObject(
            new SelectType(
                $this->createMock(TranslatorInterface::class),
                $this->createMock(FilterOperatorProviderInterface::class)
            )
        );
        $this->expectException(NotFoundException::class);
        $customField->valueToLabel('option_b');
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

    public function testCanHaveMultipleValuesForDateType()
    {
        $typeObject = new DateType(
            $this->createMock(TranslatorInterface::class),
            $this->createMock(FilterOperatorProviderInterface::class)
        );
        $customField = new CustomField();
        $customField->setTypeObject($typeObject);

        $this->assertFalse($customField->canHaveMultipleValues());
    }

    public function testCanHaveMultipleValuesForCheckboxType()
    {
        $typeObject = new CheckboxGroupType(
            $this->createMock(TranslatorInterface::class),
            $this->createMock(FilterOperatorProviderInterface::class),
            $this->createMock(CsvHelper::class)
        );
        $customField = new CustomField();
        $customField->setTypeObject($typeObject);

        $this->assertTrue($customField->canHaveMultipleValues());
    }
}
