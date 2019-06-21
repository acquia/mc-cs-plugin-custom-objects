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
use MauticPlugin\CustomObjectsBundle\Exception\InvalidValueException;
use MauticPlugin\CustomObjectsBundle\CustomFieldType\DateTimeType;
use MauticPlugin\CustomObjectsBundle\Entity\CustomFieldValueDateTime;
use MauticPlugin\CustomObjectsBundle\CustomFieldType\DataTransformer\DateTimeTransformer;
use MauticPlugin\CustomObjectsBundle\CustomFieldType\DataTransformer\ViewDateTransformer;
use MauticPlugin\CustomObjectsBundle\CustomFieldType\DataTransformer\DateTimeAtomTransformer;

class DateTimeTypeTest extends \PHPUnit_Framework_TestCase
{
    private $translator;
    private $customField;
    private $customItem;

    /**
     * @var DateTimeType
     */
    private $fieldType;

    protected function setUp(): void
    {
        parent::setUp();

        $this->translator  = $this->createMock(TranslatorInterface::class);
        $this->customField = $this->createMock(CustomField::class);
        $this->customItem  = $this->createMock(CustomItem::class);
        $this->fieldType   = new DateTimeType($this->translator);
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
        $operators = $this->fieldType->getOperators();

        $this->assertCount(8, $operators);
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
}
