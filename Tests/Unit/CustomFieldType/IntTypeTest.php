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
use MauticPlugin\CustomObjectsBundle\CustomFieldType\IntType;
use MauticPlugin\CustomObjectsBundle\Entity\CustomFieldValueInt;

class IntTypeTest extends \PHPUnit\Framework\TestCase
{
    private $translator;
    private $customField;
    private $customItem;

    /**
     * @var IntType
     */
    private $fieldType;

    protected function setUp(): void
    {
        parent::setUp();

        $this->translator  = $this->createMock(TranslatorInterface::class);
        $this->customField = $this->createMock(CustomField::class);
        $this->customItem  = $this->createMock(CustomItem::class);
        $this->fieldType   = new IntType($this->translator);
    }

    public function testGetSymfonyFormFieldType(): void
    {
        $this->assertSame(
            \Symfony\Component\Form\Extension\Core\Type\NumberType::class,
            $this->fieldType->getSymfonyFormFieldType()
        );
    }

    public function testGetEntityClass(): void
    {
        $this->assertSame(
            CustomFieldValueInt::class,
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

    public function testCreateValueEntity(): void
    {
        $valueEntity = $this->fieldType->createValueEntity(
            $this->customField,
            $this->customItem,
            234
        );

        $this->assertInstanceOf(CustomFieldValueInt::class, $valueEntity);
        $this->assertSame($this->customField, $valueEntity->getCustomField());
        $this->assertSame($this->customItem, $valueEntity->getCustomItem());
        $this->assertSame(234, $valueEntity->getValue());
    }
}
