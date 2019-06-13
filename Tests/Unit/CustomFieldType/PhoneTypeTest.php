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
use MauticPlugin\CustomObjectsBundle\Entity\CustomFieldValueText;
use MauticPlugin\CustomObjectsBundle\Entity\CustomField;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use MauticPlugin\CustomObjectsBundle\Entity\CustomItem;
use Symfony\Component\Validator\Violation\ConstraintViolationBuilderInterface;
use MauticPlugin\CustomObjectsBundle\CustomFieldType\PhoneType;

class PhoneTypeTest extends \PHPUnit_Framework_TestCase
{
    private $translator;
    private $customField;
    private $customItem;
    private $context;
    private $violation;

    /**
     * @var PhoneType
     */
    private $fieldType;

    protected function setUp()
    {
        parent::setUp();

        $this->translator = $this->createMock(TranslatorInterface::class);
        $this->customField = $this->createMock(CustomField::class);
        $this->customItem = $this->createMock(CustomItem::class);
        $this->context = $this->createMock(ExecutionContextInterface::class);
        $this->violation = $this->createMock(ConstraintViolationBuilderInterface::class);
        $this->fieldType = new PhoneType($this->translator);

        $this->context->method('buildViolation')->willReturn($this->violation);
    }

    public function testValidateValueWithBogusString(): void
    {
        $valueEntity = new CustomFieldValueText($this->customField, $this->customItem, 'unicorn');

        $this->violation->expects($this->once())
            ->method('atPath')
            ->with('value')
            ->willReturnSelf();

        $this->violation->expects($this->once())
            ->method('addViolation');

        $this->fieldType->validateValue($valueEntity, $this->context);
    }

    public function testValidateValueWithEmptyString(): void
    {
        $valueEntity = new CustomFieldValueText($this->customField, $this->customItem, '');

        $this->violation->expects($this->never())
            ->method('atPath');

        $this->violation->expects($this->never())
            ->method('addViolation');

        $this->fieldType->validateValue($valueEntity, $this->context);
    }

    public function testValidateValueWithInvalidPhoneNumberFormat(): void
    {
        $valueEntity = new CustomFieldValueText($this->customField, $this->customItem, '8889227842');

        $this->violation->expects($this->once())
            ->method('atPath')
            ->with('value')
            ->willReturnSelf();

        $this->violation->expects($this->once())
            ->method('addViolation');

        $this->fieldType->validateValue($valueEntity, $this->context);
    }

    public function testValidateValueWithWrongRegionPrefix(): void
    {
        $valueEntity = new CustomFieldValueText($this->customField, $this->customItem, '+42 (0) 78 927 2696');

        $this->violation->expects($this->once())
            ->method('atPath')
            ->with('value')
            ->willReturnSelf();

        $this->violation->expects($this->once())
            ->method('addViolation');

        $this->fieldType->validateValue($valueEntity, $this->context);
    }

    public function testValidateValueWithValidPhoneNumber(): void
    {
        $valueEntity = new CustomFieldValueText($this->customField, $this->customItem, '+1 888 922 7842');

        $this->violation->expects($this->never())
            ->method('atPath');

        $this->violation->expects($this->never())
            ->method('addViolation');

        $this->fieldType->validateValue($valueEntity, $this->context);
    }
}
