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
use MauticPlugin\CustomObjectsBundle\Entity\CustomItem;
use MauticPlugin\CustomObjectsBundle\Entity\CustomFieldValueOption;
use MauticPlugin\CustomObjectsBundle\CustomFieldType\AbstractMultivalueType;
use MauticPlugin\CustomObjectsBundle\Helper\CsvHelper;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use MauticPlugin\CustomObjectsBundle\CustomFieldType\DataTransformer\MultivalueTransformer;
use Doctrine\Common\Collections\ArrayCollection;
use MauticPlugin\CustomObjectsBundle\Entity\CustomFieldOption;
use MauticPlugin\CustomObjectsBundle\CustomFieldType\DataTransformer\CsvTransformer;

class AbstractMultivalueTypeTest extends \PHPUnit_Framework_TestCase
{
    private $translator;
    private $customField;
    private $customItem;

    /**
     * @var AbstractMultivalueType
     */
    private $fieldType;

    protected function setUp(): void
    {
        parent::setUp();

        $this->translator  = $this->createMock(TranslatorInterface::class);
        $this->customField = $this->createMock(CustomField::class);
        $this->customItem  = $this->createMock(CustomItem::class);
        $this->fieldType   = $this->getMockForAbstractClass(
            AbstractMultivalueType::class,
            [$this->translator, new CsvHelper()]
        );
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

    public function testCreateApiValueTransformer(): void
    {
        $this->isInstanceOf(
            CsvTransformer::class,
            $this->fieldType->createApiValueTransformer()
        );
    }

    public function testValidateValueWithEmptyArray(): void
    {
        $this->customField->expects($this->never())
            ->method('getOptions');

        $this->fieldType->validateValue($this->customField, []);
    }

    public function testValidateValueWithValidSingleOptionString(): void
    {
        $option1 = new CustomFieldOption();
        $option2 = new CustomFieldOption();
        $option1->setValue('one');
        $option2->setValue('two');

        $this->customField->expects($this->once())
            ->method('getOptions')
            ->willReturn(new ArrayCollection([$option1, $option2]));

        $this->fieldType->validateValue($this->customField, 'two');
    }

    public function testValidateValueWithValidOptions(): void
    {
        $option1 = new CustomFieldOption();
        $option2 = new CustomFieldOption();
        $option3 = new CustomFieldOption();
        $option1->setValue('one');
        $option2->setValue('two');
        $option3->setValue('3');

        $this->customField->expects($this->once())
            ->method('getOptions')
            ->willReturn(new ArrayCollection([$option1, $option2, $option3]));

        $this->fieldType->validateValue($this->customField, ['one', 'two', 3]);
    }

    public function testValidateValueWithInvalidSingleOptionString(): void
    {
        $option1 = new CustomFieldOption();
        $option2 = new CustomFieldOption();
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

        $this->expectException(\UnexpectedValueException::class);
        $this->fieldType->validateValue($this->customField, 'unicorn');
    }

    public function testValidateValueWithValidSingleOptionJsonString(): void
    {
        $option1 = new CustomFieldOption();
        $option2 = new CustomFieldOption();
        $option1->setValue('one');
        $option2->setValue('two');

        $this->customField->expects($this->once())
            ->method('getOptions')
            ->willReturn(new ArrayCollection([$option1, $option2]));

        $this->fieldType->validateValue($this->customField, '["one","two"]');
    }

    public function testValidateValueWithInvalidOptions(): void
    {
        $option1 = new CustomFieldOption();
        $option2 = new CustomFieldOption();
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

        $this->expectException(\UnexpectedValueException::class);
        $this->fieldType->validateValue($this->customField, ['one', 'unicorn']);
    }
}
