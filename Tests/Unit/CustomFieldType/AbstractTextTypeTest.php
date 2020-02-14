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

use MauticPlugin\CustomObjectsBundle\CustomFieldType\AbstractTextType;
use MauticPlugin\CustomObjectsBundle\Entity\CustomField;
use MauticPlugin\CustomObjectsBundle\Entity\CustomFieldValueText;
use MauticPlugin\CustomObjectsBundle\Entity\CustomItem;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Translation\TranslatorInterface;

class AbstractTextTypeTest extends \PHPUnit\Framework\TestCase
{
    private $translator;
    private $customField;
    private $customItem;

    /**
     * @var AbstractTextType
     */
    private $fieldType;

    protected function setUp(): void
    {
        parent::setUp();

        $this->translator  = $this->createMock(TranslatorInterface::class);
        $this->customField = $this->createMock(CustomField::class);
        $this->customItem  = $this->createMock(CustomItem::class);
        $this->fieldType   = $this->getMockForAbstractClass(
            AbstractTextType::class,
            [$this->translator]
        );
    }

    public function testCreateValueEntity(): void
    {
        $value       = 'balada';
        $valueOption = $this->fieldType->createValueEntity($this->customField, $this->customItem, $value);

        $this->assertInstanceOf(CustomFieldValueText::class, $valueOption);
        $this->assertSame($value, $valueOption->getValue());
        $this->assertSame($this->customField, $valueOption->getCustomField());
        $this->assertSame($this->customItem, $valueOption->getCustomItem());
    }

    public function testGetSymfonyFormFieldType(): void
    {
        $this->assertSame(
            TextType::class,
            $this->fieldType->getSymfonyFormFieldType()
        );
    }

    public function testGetEntityClass(): void
    {
        $this->assertSame(
            CustomFieldValueText::class,
            $this->fieldType->getEntityClass()
        );
    }

    public function testGetOperators(): void
    {
        $operators = $this->fieldType->getOperators();

        $this->assertArrayHasKey('=', $operators);
        $this->assertArrayHasKey('!=', $operators);
        $this->assertArrayHasKey('empty', $operators);
        $this->assertArrayHasKey('!empty', $operators);
        $this->assertArrayHasKey('like', $operators);
        $this->assertArrayHasKey('!like', $operators);
        $this->assertArrayHasKey('startsWith', $operators);
        $this->assertArrayHasKey('endsWith', $operators);
        $this->assertArrayHasKey('contains', $operators);
    }
}
