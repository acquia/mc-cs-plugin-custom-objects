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
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use MauticPlugin\CustomObjectsBundle\Entity\CustomItem;
use Symfony\Component\Validator\Violation\ConstraintViolationBuilderInterface;
use MauticPlugin\CustomObjectsBundle\CustomFieldType\EmailType;
use MauticPlugin\CustomObjectsBundle\CustomFieldType\AbstractCustomFieldType;
use MauticPlugin\CustomObjectsBundle\Entity\CustomFieldValueText;
use MauticPlugin\CustomObjectsBundle\Entity\CustomFieldValueInt;
use MauticPlugin\CustomObjectsBundle\Entity\CustomFieldValueOption;
use MauticPlugin\CustomObjectsBundle\Exception\UndefinedTransformerException;

class AbstractCustomFieldTypeTest extends \PHPUnit_Framework_TestCase
{
    private $translator;
    private $customField;
    private $customItem;
    private $context;
    private $violation;

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
        $this->customItem  = $this->createMock(CustomItem::class);
        $this->context     = $this->createMock(ExecutionContextInterface::class);
        $this->violation   = $this->createMock(ConstraintViolationBuilderInterface::class);
        $this->fieldType   = $this->getMockForAbstractClass(
            AbstractCustomFieldType::class,
            [$this->translator]
        );

        $this->context->method('buildViolation')->willReturn($this->violation);
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
        $valueEntity = new CustomFieldValueText($this->customField, $this->customItem, '');

        $this->customField->expects($this->once())
            ->method('isRequired')
            ->willReturn(false);

        $this->context->expects($this->never())
            ->method('buildViolation');

        $this->fieldType->validateValue($valueEntity, $this->context);
    }

    public function testValidateValueWhenFieldIsRequiredAndNotEmptyString(): void
    {
        $valueEntity = new CustomFieldValueText($this->customField, $this->customItem, 'unicorn');

        $this->customField->expects($this->once())
            ->method('isRequired')
            ->willReturn(true);

        $this->context->expects($this->never())
            ->method('buildViolation');

        $this->fieldType->validateValue($valueEntity, $this->context);
    }

    public function testValidateValueWhenFieldIsRequiredAndNotZero(): void
    {
        $valueEntity = new CustomFieldValueInt($this->customField, $this->customItem, 0);

        $this->customField->expects($this->once())
            ->method('isRequired')
            ->willReturn(true);

        $this->context->expects($this->never())
            ->method('buildViolation');

        $this->fieldType->validateValue($valueEntity, $this->context);
    }

    public function testValidateValueWhenFieldIsRequiredAndNotEmptyArray(): void
    {
        $valueEntity = new CustomFieldValueOption($this->customField, $this->customItem, []);

        $this->customField->expects($this->once())
            ->method('isRequired')
            ->willReturn(true);

        $this->context->expects($this->once())
            ->method('buildViolation');

        $this->violation->expects($this->once())
            ->method('atPath')
            ->with('value')
            ->willReturnSelf();

        $this->violation->expects($this->once())
            ->method('addViolation');

        $this->fieldType->validateValue($valueEntity, $this->context);
    }

    public function testValidateValueWhenFieldIsRequiredAndEmptyString(): void
    {
        $valueEntity = new CustomFieldValueText($this->customField, $this->customItem, '');

        $this->customField->expects($this->once())
            ->method('isRequired')
            ->willReturn(true);

        $this->context->expects($this->once())
            ->method('buildViolation');

        $this->violation->expects($this->once())
            ->method('atPath')
            ->with('value')
            ->willReturnSelf();

        $this->violation->expects($this->once())
            ->method('addViolation');

        $this->fieldType->validateValue($valueEntity, $this->context);
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
