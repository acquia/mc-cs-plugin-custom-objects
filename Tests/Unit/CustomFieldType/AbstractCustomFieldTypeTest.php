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
use MauticPlugin\CustomObjectsBundle\CustomFieldType\EmailType;
use MauticPlugin\CustomObjectsBundle\CustomFieldType\AbstractCustomFieldType;
use MauticPlugin\CustomObjectsBundle\Exception\UndefinedTransformerException;

class AbstractCustomFieldTypeTest extends \PHPUnit_Framework_TestCase
{
    private $translator;
    private $customField;

    /**
     * @var EmailType
     */
    private $fieldType;

    protected function setUp(): void
    {
        parent::setUp();

        defined('MAUTIC_TABLE_PREFIX') or define('MAUTIC_TABLE_PREFIX', '');

        $this->translator  = $this->createMock(TranslatorInterface::class);
        $this->customField = $this->createMock(CustomField::class);
        $this->fieldType   = $this->getMockForAbstractClass(
            AbstractCustomFieldType::class,
            [$this->translator]
        );
    }

    public function testToString(): void
    {
        $this->assertSame('undefined', (string) $this->fieldType);
    }

    public function testGetName(): void
    {
        $this->translator->expects($this->once())
            ->method('trans')
            ->with('undefined')
            ->willReturn('tanslated name');
        $this->assertSame('tanslated name', $this->fieldType->getName());
    }

    public function testGetKey(): void
    {
        $this->assertSame('undefined', $this->fieldType->getKey());
    }

    public function testGetTableAlias(): void
    {
        $this->assertSame('cfv_undefined', $this->fieldType->getTableAlias());
    }

    public function testGetOperators(): void
    {
        $this->assertArrayHasKey('=', $this->fieldType->getOperators());
    }

    public function testGetTableName(): void
    {
        $this->assertSame('undefined', $this->fieldType->getTableName());
    }

    public function testGetOperatorOptions(): void
    {
        $this->assertArrayHasKey('=', $this->fieldType->getOperatorOptions());
    }

    public function testCreateFormTypeOptions(): void
    {
        $this->assertSame(['added' => 'option'], $this->fieldType->createFormTypeOptions(['added' => 'option']));
    }

    public function testHasChoices(): void
    {
        $this->assertFalse($this->fieldType->hasChoices());
    }

    public function testValidateValueWhenFieldIsNotRequired(): void
    {
        $this->customField->expects($this->once())
            ->method('isRequired')
            ->willReturn(false);

        $this->fieldType->validateValue($this->customField, '');
    }

    public function testValidateValueWhenFieldIsRequiredAndNotEmptyString(): void
    {
        $this->customField->expects($this->once())
            ->method('isRequired')
            ->willReturn(true);

        $this->fieldType->validateValue($this->customField, 'unicorn');
    }

    public function testValidateValueWhenFieldIsRequiredAndNotZero(): void
    {
        $this->customField->expects($this->once())
            ->method('isRequired')
            ->willReturn(true);

        $this->fieldType->validateValue($this->customField, 0);
    }

    public function testValidateValueWhenFieldIsRequiredAndNotEmptyArray(): void
    {
        $this->customField->expects($this->once())
            ->method('isRequired')
            ->willReturn(true);

        $this->customField->method('getLabel')->willReturn('Field A');
        $this->customField->method('getAlias')->willReturn('field-a');

        $this->translator->expects($this->once())
            ->method('trans')
            ->with(
                'custom.field.required',
                ['%fieldName%' => 'Field A (field-a)'],
                'validators'
            )
            ->willReturn('Translated message');

        $this->expectException(\UnexpectedValueException::class);
        $this->fieldType->validateValue($this->customField, []);
    }

    public function testValidateValueWhenFieldIsRequiredAndEmptyString(): void
    {
        $this->customField->expects($this->once())
            ->method('isRequired')
            ->willReturn(true);

        $this->customField->method('getLabel')->willReturn('Field A');
        $this->customField->method('getAlias')->willReturn('field-a');

        $this->translator->expects($this->once())
            ->method('trans')
            ->with(
                'custom.field.required',
                ['%fieldName%' => 'Field A (field-a)'],
                'validators'
            )
            ->willReturn('Translated message');

        $this->expectException(\UnexpectedValueException::class);
        $this->fieldType->validateValue($this->customField, '');
    }

    public function testUseEmptyValue(): void
    {
        $this->assertFalse($this->fieldType->useEmptyValue());
    }

    public function testCreateDefaultValueTransformer(): void
    {
        $this->expectException(UndefinedTransformerException::class);
        $this->assertFalse($this->fieldType->createDefaultValueTransformer());
    }
}
