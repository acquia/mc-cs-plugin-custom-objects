<?php

declare(strict_types=1);

namespace MauticPlugin\CustomObjectsBundle\Tests\Unit\CustomFieldType;

use Mautic\LeadBundle\Provider\FilterOperatorProviderInterface;
use MauticPlugin\CustomObjectsBundle\CustomFieldType\DataTransformer\DateTimeAtomTransformer;
use MauticPlugin\CustomObjectsBundle\CustomFieldType\DataTransformer\DateTimeTransformer;
use MauticPlugin\CustomObjectsBundle\CustomFieldType\DataTransformer\ViewDateTransformer;
use MauticPlugin\CustomObjectsBundle\CustomFieldType\DateTimeType;
use MauticPlugin\CustomObjectsBundle\Entity\CustomField;
use MauticPlugin\CustomObjectsBundle\Entity\CustomFieldValueDateTime;
use MauticPlugin\CustomObjectsBundle\Entity\CustomItem;
use MauticPlugin\CustomObjectsBundle\Exception\InvalidValueException;
use Symfony\Contracts\Translation\TranslatorInterface;

class DateTimeTypeTest extends \PHPUnit\Framework\TestCase
{
    private $translator;
    private $customField;
    private $customItem;
    private $filterOperatorProvider;

    /**
     * @var DateTimeType
     */
    private $fieldType;

    protected function setUp(): void
    {
        parent::setUp();

        $this->translator             = $this->createMock(TranslatorInterface::class);
        $this->customField            = $this->createMock(CustomField::class);
        $this->customItem             = $this->createMock(CustomItem::class);
        $this->filterOperatorProvider = $this->createMock(FilterOperatorProviderInterface::class);
        $this->fieldType              = new DateTimeType(
            $this->translator,
            $this->filterOperatorProvider
        );
    }

    public function testCreateValueEntityWithoutValue(): void
    {
        $valueEntity = $this->fieldType->createValueEntity(
            $this->customField,
            $this->customItem
        );

        $this->assertSame($this->customField, $valueEntity->getCustomField());
        $this->assertSame($this->customItem, $valueEntity->getCustomItem());
        $this->assertSame(null, $valueEntity->getValue());
    }

    public function testCreateValueEntityWithValidDate(): void
    {
        $valueEntity = $this->fieldType->createValueEntity(
            $this->customField,
            $this->customItem,
            '2019-06-18 13:16:34'
        );

        $this->assertSame($this->customField, $valueEntity->getCustomField());
        $this->assertSame($this->customItem, $valueEntity->getCustomItem());
        $this->assertSame('2019-06-18 13:16:34', $valueEntity->getValue()->format('Y-m-d H:i:s'));
    }

    public function testCreateValueEntityWithInvalidDate(): void
    {
        $this->expectException(InvalidValueException::class);
        $this->fieldType->createValueEntity(
            $this->customField,
            $this->customItem,
            'unicorn'
        );
    }

    public function testGetSymfonyFormFieldType(): void
    {
        $this->assertSame(
            \Symfony\Component\Form\Extension\Core\Type\DateTimeType::class,
            $this->fieldType->getSymfonyFormFieldType()
        );
    }

    public function testGetEntityClass(): void
    {
        $this->assertSame(
            CustomFieldValueDateTime::class,
            $this->fieldType->getEntityClass()
        );
    }

    public function testGetOperators(): void
    {
        $this->filterOperatorProvider->expects($this->once())
            ->method('getAllOperators')
            ->willReturn([
                'empty'         => [],
                '!empty'        => [],
                'in'            => [],
                '='             => [],
                '!='            => [],
                'somethingelse' => [],
            ]);

        $operators = $this->fieldType->getOperators();

        $this->assertArrayHasKey('=', $operators);
        $this->assertArrayNotHasKey('in', $operators);
    }

    public function testCreateDefaultValueTransformer(): void
    {
        $this->assertInstanceOf(
            DateTimeTransformer::class,
            $this->fieldType->createDefaultValueTransformer()
        );
    }

    public function testCreateApiValueTransformer(): void
    {
        $this->assertInstanceOf(
            DateTimeAtomTransformer::class,
            $this->fieldType->createApiValueTransformer()
        );
    }

    public function testCreateViewTransformer(): void
    {
        $this->assertInstanceOf(
            ViewDateTransformer::class,
            $this->fieldType->createViewTransformer()
        );
    }

    public function testValueToStringWithDateTimeValue(): void
    {
        $fieldValue = new CustomFieldValueDateTime($this->customField, $this->customItem, new \DateTime('2019-07-16 14:22:11'));

        $this->assertSame('2019-07-16 14:22:11', $this->fieldType->valueToString($fieldValue));
    }

    public function testValueToStringWithStringValue(): void
    {
        $fieldValue = new CustomFieldValueDateTime($this->customField, $this->customItem);

        $fieldValue->setValue('2019-07-16 14:22:11');

        $this->assertSame('2019-07-16 14:22:11', $this->fieldType->valueToString($fieldValue));
    }

    public function testValueToStringWithNullValue(): void
    {
        $fieldValue = new CustomFieldValueDateTime($this->customField, $this->customItem);

        $this->assertSame('', $this->fieldType->valueToString($fieldValue));
    }
}
