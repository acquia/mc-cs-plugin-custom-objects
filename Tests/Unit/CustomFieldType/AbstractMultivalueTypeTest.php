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
use MauticPlugin\CustomObjectsBundle\Entity\CustomFieldValueOption;
use MauticPlugin\CustomObjectsBundle\CustomFieldType\AbstractMultivalueType;
use MauticPlugin\CustomObjectsBundle\Helper\CsvHelper;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use MauticPlugin\CustomObjectsBundle\CustomFieldType\DataTransformer\MultivalueTransformer;
use Doctrine\Common\Collections\ArrayCollection;
use MauticPlugin\CustomObjectsBundle\Entity\CustomFieldOption;

class AbstractMultivalueTypeTest extends \PHPUnit_Framework_TestCase
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

        $this->translator  = $this->createMock(TranslatorInterface::class);
        $this->customField = $this->createMock(CustomField::class);
        $this->customItem  = $this->createMock(CustomItem::class);
        $this->context     = $this->createMock(ExecutionContextInterface::class);
        $this->violation   = $this->createMock(ConstraintViolationBuilderInterface::class);
        $this->fieldType   = $this->getMockForAbstractClass(
            AbstractMultivalueType::class,
            [$this->translator, new CsvHelper()]
        );

        $this->context->method('buildViolation')->willReturn($this->violation);
    }

    public function testCreateValueEntity(): void
    {
        $value       = ['one', 'three'];
        $valueOption = $this->fieldType->createValueEntity($this->customField, $this->customItem, $value);

        $this->assertSame($value, $valueOption->getValue());
        $this->assertSame($this->customField, $valueOption->getCustomField());
        $this->assertSame($this->customItem, $valueOption->getCustomItem());
    }

    public function testGetSymfonyFormFieldType(): void
    {
        $this->assertSame(
            ChoiceType::class,
            $this->fieldType->getSymfonyFormFieldType()
        );
    }

    public function testGetEntityClass(): void
    {
        $this->assertSame(
            CustomFieldValueOption::class,
            $this->fieldType->getEntityClass()
        );
    }

    public function testGetOperators(): void
    {
        $operators = $this->fieldType->getOperators();

        $this->assertCount(4, $operators);
        $this->assertArrayHasKey('empty', $operators);
        $this->assertArrayHasKey('!empty', $operators);
        $this->assertArrayHasKey('in', $operators);
        $this->assertArrayHasKey('!in', $operators);
    }

    public function testCreateDefaultValueTransformer(): void
    {
        $this->isInstanceOf(
            MultivalueTransformer::class,
            $this->fieldType->createDefaultValueTransformer()
        );
    }

    public function testValidateValueWithEmptyArray(): void
    {
        $valueEntity = new CustomFieldValueOption($this->customField, $this->customItem, []);

        $this->customField->expects($this->never())
            ->method('getOptions');

        $this->fieldType->validateValue($valueEntity, $this->context);
    }

    public function testValidateValueWithValidSingleOptionString(): void
    {
        $valueEntity = new CustomFieldValueOption($this->customField, $this->customItem, 'two');
        $option1     = new CustomFieldOption();
        $option2     = new CustomFieldOption();
        $option1->setValue('one');
        $option2->setValue('two');

        $this->customField->expects($this->once())
            ->method('getOptions')
            ->willReturn(new ArrayCollection([$option1, $option2]));

        $this->violation->expects($this->never())
            ->method('atPath');

        $this->fieldType->validateValue($valueEntity, $this->context);
    }

    public function testValidateValueWithValidTwoOption(): void
    {
        $valueEntity = new CustomFieldValueOption($this->customField, $this->customItem, ['one', 'two']);
        $option1     = new CustomFieldOption();
        $option2     = new CustomFieldOption();
        $option1->setValue('one');
        $option2->setValue('two');

        $this->customField->expects($this->once())
            ->method('getOptions')
            ->willReturn(new ArrayCollection([$option1, $option2]));

        $this->violation->expects($this->never())
            ->method('atPath');

        $this->fieldType->validateValue($valueEntity, $this->context);
    }

    public function testValidateValueWithInvalidSingleOptionString(): void
    {
        $valueEntity = new CustomFieldValueOption($this->customField, $this->customItem, 'unicorn');
        $option1     = new CustomFieldOption();
        $option2     = new CustomFieldOption();
        $option1->setValue('one');
        $option2->setValue('two');

        $this->customField->expects($this->once())
            ->method('getOptions')
            ->willReturn(new ArrayCollection([$option1, $option2]));

        $this->translator->expects($this->once())
            ->method('trans')
            ->with(
                'custom.field.option.invalid',
                [
                    '%value%'          => 'unicorn',
                    '%fieldLabel%'     => null,
                    '%fieldAlias%'     => null,
                    '%possibleValues%' => 'one,two',
                ],
                'validators'
            )
            ->willReturn('Translated message');

        $this->violation->expects($this->once())
            ->method('atPath')
            ->with('value')
            ->willReturnSelf();

        $this->violation->expects($this->once())
            ->method('addViolation');

        $this->fieldType->validateValue($valueEntity, $this->context);
    }

    public function testValidateValueWithInvalidOptions(): void
    {
        $valueEntity = new CustomFieldValueOption($this->customField, $this->customItem, ['one', 'unicorn']);
        $option1     = new CustomFieldOption();
        $option2     = new CustomFieldOption();
        $option1->setValue('one');
        $option2->setValue('two');

        $this->customField->expects($this->once())
            ->method('getOptions')
            ->willReturn(new ArrayCollection([$option1, $option2]));

        $this->translator->expects($this->once())
            ->method('trans')
            ->with(
                'custom.field.option.invalid',
                [
                    '%value%'          => 'unicorn',
                    '%fieldLabel%'     => null,
                    '%fieldAlias%'     => null,
                    '%possibleValues%' => 'one,two',
                ],
                'validators'
            )
            ->willReturn('Translated message');

        $this->violation->expects($this->once())
            ->method('atPath')
            ->with('value')
            ->willReturnSelf();

        $this->violation->expects($this->once())
            ->method('addViolation');

        $this->fieldType->validateValue($valueEntity, $this->context);
    }
}
