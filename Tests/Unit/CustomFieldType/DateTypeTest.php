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
use MauticPlugin\CustomObjectsBundle\CustomFieldType\DateType;
use MauticPlugin\CustomObjectsBundle\Entity\CustomItem;
use MauticPlugin\CustomObjectsBundle\Exception\InvalidValueException;
use MauticPlugin\CustomObjectsBundle\Entity\CustomFieldValueDate;
use MauticPlugin\CustomObjectsBundle\CustomFieldType\DataTransformer\DateTransformer;
use MauticPlugin\CustomObjectsBundle\CustomFieldType\DataTransformer\ViewDateTransformer;

class DateTypeTest extends \PHPUnit_Framework_TestCase
{
    private $translator;
    private $customField;
    private $customItem;

    /**
     * @var DateType
     */
    private $fieldType;

    protected function setUp(): void
    {
        parent::setUp();

        $this->translator  = $this->createMock(TranslatorInterface::class);
        $this->customField = $this->createMock(CustomField::class);
        $this->customItem  = $this->createMock(CustomItem::class);
        $this->fieldType   = new DateType($this->translator);
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
            '2019-06-18'
        );

        $this->assertSame($this->customField, $valueEntity->getCustomField());
        $this->assertSame($this->customItem, $valueEntity->getCustomItem());
        $this->assertSame('2019-06-18', $valueEntity->getValue()->format('Y-m-d'));
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
            \Symfony\Component\Form\Extension\Core\Type\DateType::class,
            $this->fieldType->getSymfonyFormFieldType()
        );
    }

    public function testGetEntityClass(): void
    {
        $this->assertSame(
            CustomFieldValueDate::class,
            $this->fieldType->getEntityClass()
        );
    }

    public function testGetOperators(): void
    {
        $operators = $this->fieldType->getOperators();

        $this->assertCount(8, $operators);
        $this->assertArrayHasKey('=', $operators);
        $this->assertArrayNotHasKey('in', $operators);
    }

    public function testCreateDefaultValueTransformer(): void
    {
        $this->assertInstanceOf(
            DateTransformer::class,
            $this->fieldType->createDefaultValueTransformer()
        );
    }

    public function testCreateApiValueTransformer(): void
    {
        $this->assertInstanceOf(
            DateTransformer::class,
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

    public function testValueToString(): void
    {
        $this->assertSame('2019-07-16', $this->fieldType->valueToString(new \DateTime('2019-07-16')));
        $this->assertSame('2019-07-16', $this->fieldType->valueToString('2019-07-16'));
    }
}
