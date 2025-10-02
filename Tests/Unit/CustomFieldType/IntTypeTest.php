<?php

declare(strict_types=1);

namespace MauticPlugin\CustomObjectsBundle\Tests\Unit\CustomFieldType;

use Mautic\LeadBundle\Provider\FilterOperatorProviderInterface;
use MauticPlugin\CustomObjectsBundle\CustomFieldType\IntType;
use MauticPlugin\CustomObjectsBundle\Entity\CustomField;
use MauticPlugin\CustomObjectsBundle\Entity\CustomFieldValueInt;
use MauticPlugin\CustomObjectsBundle\Entity\CustomItem;
use Symfony\Contracts\Translation\TranslatorInterface;

class IntTypeTest extends \PHPUnit\Framework\TestCase
{
    private $translator;
    private $customField;
    private $customItem;
    private $filterOperatorProvider;

    /**
     * @var IntType
     */
    private $fieldType;

    protected function setUp(): void
    {
        parent::setUp();

        $this->translator             = $this->createMock(TranslatorInterface::class);
        $this->customField            = $this->createMock(CustomField::class);
        $this->customItem             = $this->createMock(CustomItem::class);
        $this->filterOperatorProvider = $this->createMock(FilterOperatorProviderInterface::class);
        $this->fieldType              = new IntType(
            $this->translator,
            $this->filterOperatorProvider
        );
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
        $this->filterOperatorProvider->expects($this->once())
            ->method('getAllOperators')
            ->willReturn([
                'empty'  => [],
                '!empty' => [],
                'in'     => [],
                '='      => [],
                '!='     => [],
            ]);

        $operators = $this->fieldType->getOperators();

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
