<?php

declare(strict_types=1);

namespace MauticPlugin\CustomObjectsBundle\Tests\Unit\CustomFieldType;

use Doctrine\Common\Collections\ArrayCollection;
use Mautic\LeadBundle\Provider\FilterOperatorProviderInterface;
use MauticPlugin\CustomObjectsBundle\CustomFieldType\AbstractMultivalueType;
use MauticPlugin\CustomObjectsBundle\CustomFieldType\DataTransformer\CsvTransformer;
use MauticPlugin\CustomObjectsBundle\CustomFieldType\DataTransformer\MultivalueTransformer;
use MauticPlugin\CustomObjectsBundle\Entity\CustomField;
use MauticPlugin\CustomObjectsBundle\Entity\CustomFieldOption;
use MauticPlugin\CustomObjectsBundle\Entity\CustomFieldValueOption;
use MauticPlugin\CustomObjectsBundle\Entity\CustomItem;
use MauticPlugin\CustomObjectsBundle\Exception\NotFoundException;
use MauticPlugin\CustomObjectsBundle\Helper\CsvHelper;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Contracts\Translation\TranslatorInterface;

class AbstractMultivalueTypeTest extends \PHPUnit\Framework\TestCase
{
    private $translator;
    private $customField;
    private $customItem;
    private $provider;

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
        $this->provider    = $this->createMock(FilterOperatorProviderInterface::class);
        $this->fieldType   = $this->getMockForAbstractClass(
            AbstractMultivalueType::class,
            [$this->translator, $this->provider, new CsvHelper()]
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
        $this->provider->expects($this->once())
            ->method('getAllOperators')
            ->willReturn([
                'empty'         => [],
                '!empty'        => [],
                'in'            => [],
                '!in'           => [],
                'somethingelse' => [],
                ]);

        $operators = $this->fieldType->getOperators();

        $this->assertCount(4, $operators);
        $this->assertArrayHasKey('empty', $operators);
        $this->assertArrayHasKey('!empty', $operators);
        $this->assertArrayHasKey('in', $operators);
        $this->assertArrayHasKey('!in', $operators);
    }

    public function testCreateDefaultValueTransformer(): void
    {
        $this->assertInstanceOf(
            MultivalueTransformer::class,
            $this->fieldType->createDefaultValueTransformer()
        );
    }

    public function testCreateApiValueTransformer(): void
    {
        $this->assertInstanceOf(
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

    public function testValueToStringWithArrayValue(): void
    {
        // Note: unicorn value does not exist in the options for this field.
        $fieldValue = new CustomFieldValueOption($this->customField, $this->customItem, ['one', 'two', 'unicorn']);

        $this->customField->expects($this->exactly(3))
            ->method('valueToLabel')
            ->withConsecutive(['one'], ['two'], ['unicorn'])
            ->will($this->onConsecutiveCalls(
                'Option 1',
                'Option2',
                $this->throwException(new NotFoundException('Value unicorn does not exist'))
            ));

        $this->assertSame('"Option 1",Option2,unicorn', $this->fieldType->valueToString($fieldValue));
    }

    public function testValueToStringWithStringValue(): void
    {
        $fieldValue = new CustomFieldValueOption($this->customField, $this->customItem, 'one');

        $this->customField->expects($this->once())
            ->method('valueToLabel')
            ->with('one')
            ->willReturn('Option 1');

        $this->assertSame('"Option 1"', $this->fieldType->valueToString($fieldValue));
    }
}
