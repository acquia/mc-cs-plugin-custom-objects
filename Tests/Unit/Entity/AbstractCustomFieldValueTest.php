<?php

declare(strict_types=1);

namespace MauticPlugin\CustomObjectsBundle\Tests\Unit\Entity;

use MauticPlugin\CustomObjectsBundle\CustomFieldType\CustomFieldTypeInterface;
use MauticPlugin\CustomObjectsBundle\Entity\AbstractCustomFieldValue;
use MauticPlugin\CustomObjectsBundle\Entity\CustomField;
use MauticPlugin\CustomObjectsBundle\Entity\CustomItem;
use MauticPlugin\CustomObjectsBundle\Entity\CustomObject;
use Symfony\Component\Validator\Constraints\Callback;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Validator\Mapping\ClassMetadata;
use Symfony\Component\Validator\Violation\ConstraintViolationBuilderInterface;

class AbstractCustomFieldValueTest extends \PHPUnit\Framework\TestCase
{
    private $customObject;
    private $customField;
    private $customItem;
    private $abstractCFValue;

    protected function setUp(): void
    {
        parent::setUp();

        $this->customObject    = new CustomObject();
        $this->customField     = $this->createMock(CustomField::class);
        $this->customItem      = new CustomItem($this->customObject);
        $this->abstractCFValue = $this->getMockForAbstractClass(
            AbstractCustomFieldValue::class,
            [$this->customField, $this->customItem]
        );
    }

    public function testGettersSetters(): void
    {
        $this->customField->expects($this->once())
            ->method('getId')
            ->willReturn(123);

        $this->assertSame($this->customField, $this->abstractCFValue->getCustomField());
        $this->assertSame($this->customItem, $this->abstractCFValue->getCustomItem());
        $this->assertSame(123, $this->abstractCFValue->getId());

        $this->expectException(\Throwable::class);
        $this->abstractCFValue->addValue();
    }

    public function testLoadValidatorMetadata(): void
    {
        $metadata = $this->createMock(ClassMetadata::class);

        $metadata->expects($this->exactly(2))
            ->method('addPropertyConstraint')
            ->withConsecutive(
                ['customField', $this->isInstanceOf(NotBlank::class)],
                ['customItem', $this->isInstanceOf(NotBlank::class)]
            );

        $metadata->expects($this->once())
            ->method('addConstraint')
            ->with($this->isInstanceOf(Callback::class));

        $this->abstractCFValue->loadValidatorMetadata($metadata);
    }

    public function testValidateValueIfValid(): void
    {
        $context   = $this->createMock(ExecutionContextInterface::class);
        $fieldType = $this->createMock(CustomFieldTypeInterface::class);

        $this->customField->expects($this->once())
            ->method('getTypeObject')
            ->willReturn($fieldType);

        $fieldType->expects($this->once())
            ->method('validateValue');

        $context->expects($this->never())
            ->method('buildViolation');

        $this->abstractCFValue->validateValue($context);
    }

    public function testValidateValueIfInvalid(): void
    {
        $context   = $this->createMock(ExecutionContextInterface::class);
        $fieldType = $this->createMock(CustomFieldTypeInterface::class);
        $violation = $this->createMock(ConstraintViolationBuilderInterface::class);

        $this->customField->expects($this->once())
            ->method('getTypeObject')
            ->willReturn($fieldType);

        $fieldType->expects($this->once())
            ->method('validateValue')
            ->will($this->throwException(new \UnexpectedValueException()));

        $context->expects($this->once())
            ->method('buildViolation')
            ->willReturn($violation);

        $violation->expects($this->once())
            ->method('atPath')
            ->with('value')
            ->willReturnSelf();

        $violation->expects($this->once())
            ->method('addViolation');

        $this->abstractCFValue->validateValue($context);
    }
}
