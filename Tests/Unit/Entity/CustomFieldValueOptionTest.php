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
use MauticPlugin\CustomObjectsBundle\Entity\CustomFieldValueOption;
use MauticPlugin\CustomObjectsBundle\Entity\CustomItem;
use MauticPlugin\CustomObjectsBundle\Entity\CustomObject;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use MauticPlugin\CustomObjectsBundle\Entity\CustomFieldOption;
use Symfony\Component\Translation\TranslatorInterface;
use MauticPlugin\CustomObjectsBundle\CustomFieldType\SelectType;
use Symfony\Component\Validator\Violation\ConstraintViolationBuilderInterface;

class CustomFieldValueOptionTest extends \PHPUnit_Framework_TestCase
{
    public function testGettersSetters(): void
    {
        $customObject = new CustomObject();
        $customField  = new CustomField();
        $value1       = 'red';
        $value2       = 'blue';
        $customItem   = new CustomItem($customObject);
        $optionValue  = new CustomFieldValueOption($customField, $customItem, $value1);

        $this->assertSame($customField, $optionValue->getCustomField());
        $this->assertSame($customItem, $optionValue->getCustomItem());
        $this->assertSame($value1, $optionValue->getValue());

        $optionValue->setValue($value2);

        $this->assertSame($value2, $optionValue->getValue());

        $optionValue = new CustomFieldValueOption($customField, $customItem);

        $optionValue->addValue($value1);
        $optionValue->addValue($value2);

        $this->assertSame([$value1, $value2], $optionValue->getValue());
    }

    public function testValidateOptionValueExistsIfExists(): void
    {
        $customObject = new CustomObject();
        $customField  = new CustomField();
        $value        = 'red';
        $customItem   = new CustomItem($customObject);
        $optionValue  = new CustomFieldValueOption($customField, $customItem, $value);
        $option       = new CustomFieldOption();
        $context      = $this->createMock(ExecutionContextInterface::class);

        $customField->setTypeObject(new SelectType($this->createMock(TranslatorInterface::class)));

        $option->setValue('red');
        $option->setLabel('Red');

        $customField->addOption($option);

        $context->expects($this->never())
            ->method('buildViolation');

        $optionValue->validateOptionValueExists($context);
    }

    public function testValidateOptionValueExistsDoesNotExist(): void
    {
        $customObject = new CustomObject();
        $customField  = new CustomField();
        $value        = 'unicorn';
        $customItem   = new CustomItem($customObject);
        $optionValue  = new CustomFieldValueOption($customField, $customItem, $value);
        $option       = new CustomFieldOption();
        $context      = $this->createMock(ExecutionContextInterface::class);
        $constraint   = $this->createMock(ConstraintViolationBuilderInterface::class);
        
        $customField->setTypeObject(new SelectType($this->createMock(TranslatorInterface::class)));

        $option->setValue('red');
        $option->setLabel('Red');

        $customField->addOption($option);

        $context->expects($this->once())
            ->method('buildViolation')
            ->with($this->stringContains("Value 'unicorn' does not exist"))
            ->willReturn($constraint);

        $constraint->expects($this->once())
            ->method('atPath')
            ->with('value')
            ->willReturnSelf();

        $optionValue->validateOptionValueExists($context);
    }
}
