<?php

declare(strict_types=1);

/*
 * @copyright   2019 Mautic, Inc. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.com
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\CustomObjectsBundle\Tests\Unit\CustomFieldType;

use Symfony\Component\Translation\TranslatorInterface;
use MauticPlugin\CustomObjectsBundle\Entity\CustomField;
use MauticPlugin\CustomObjectsBundle\CustomFieldType\PhoneType;

class PhoneTypeTest extends \PHPUnit_Framework_TestCase
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
        $this->fieldType   = new PhoneType($this->translator);
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
    }
}
