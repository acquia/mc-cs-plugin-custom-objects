<?php

declare(strict_types=1);

namespace MauticPlugin\CustomObjectsBundle\Tests\Unit\CustomFieldType;

use Mautic\LeadBundle\Provider\FilterOperatorProviderInterface;
use MauticPlugin\CustomObjectsBundle\CustomFieldType\PhoneType;
use MauticPlugin\CustomObjectsBundle\Entity\CustomField;
use Symfony\Contracts\Translation\TranslatorInterface;

class PhoneTypeTest extends \PHPUnit\Framework\TestCase
{
    private $translator;
    private $customField;

    /**
     * @var PhoneType
     */
    private $fieldType;

    protected function setUp(): void
    {
        parent::setUp();

        $this->translator  = $this->createMock(TranslatorInterface::class);
        $this->customField = $this->createMock(CustomField::class);
        $this->fieldType   = new PhoneType(
            $this->translator,
            $this->createMock(FilterOperatorProviderInterface::class)
        );
    }

    public function testValidateValueWithBogusString(): void
    {
        $this->translator->expects($this->once())
            ->method('trans')
            ->with('custom.field.phone.invalid', ['%value%' => 'unicorn'], 'validators')
            ->willReturn('Translated message');

        $this->expectException(\UnexpectedValueException::class);
        $this->fieldType->validateValue($this->customField, 'unicorn');
    }

    public function testValidateValueWithEmptyString(): void
    {
        $this->fieldType->validateValue($this->customField, '');
        // No exception means it passes
        $this->addToAssertionCount(1);
    }

    public function testValidateValueWithInvalidPhoneNumberFormat(): void
    {
        $this->translator->expects($this->once())
            ->method('trans')
            ->with('custom.field.phone.invalid', ['%value%' => '8889227842'], 'validators')
            ->willReturn('Translated message');

        $this->expectException(\UnexpectedValueException::class);
        $this->fieldType->validateValue($this->customField, '8889227842');
    }

    public function testValidateValueWithWrongRegionPrefix(): void
    {
        $this->translator->expects($this->once())
            ->method('trans')
            ->with('custom.field.phone.invalid', ['%value%' => '+42 (0) 78 927 2696'], 'validators')
            ->willReturn('Translated message');

        $this->expectException(\UnexpectedValueException::class);
        $this->fieldType->validateValue($this->customField, '+42 (0) 78 927 2696');
    }

    public function testValidateValueWithValidPhoneNumber(): void
    {
        $this->fieldType->validateValue($this->customField, '+1 888 922 7842');
        // No exception means it passes
        $this->addToAssertionCount(1);
    }
}
