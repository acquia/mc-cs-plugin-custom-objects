<?php

declare(strict_types=1);

namespace MauticPlugin\CustomObjectsBundle\Tests\Unit\CustomFieldType;

use Mautic\LeadBundle\Provider\FilterOperatorProviderInterface;
use MauticPlugin\CustomObjectsBundle\CustomFieldType\SelectType;
use MauticPlugin\CustomObjectsBundle\Entity\CustomField;
use MauticPlugin\CustomObjectsBundle\Entity\CustomFieldValueOption;
use MauticPlugin\CustomObjectsBundle\Entity\CustomItem;
use MauticPlugin\CustomObjectsBundle\Exception\NotFoundException;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Contracts\Translation\TranslatorInterface;

class SelectTypeTest extends \PHPUnit\Framework\TestCase
{
    private $translator;
    private $customField;
    private $customItem;
    private $filterOperatorProvider;

    /**
     * @var SelectType
     */
    private $fieldType;

    protected function setUp(): void
    {
        parent::setUp();

        $this->translator             = $this->createMock(TranslatorInterface::class);
        $this->customField            = $this->createMock(CustomField::class);
        $this->customItem             = $this->createMock(CustomItem::class);
        $this->filterOperatorProvider = $this->createMock(FilterOperatorProviderInterface::class);
        $this->fieldType              = new SelectType(
            $this->translator,
            $this->filterOperatorProvider
        );
    }

    public function testGetSymfonyFormFieldType(): void
    {
        $this->assertSame(ChoiceType::class, $this->fieldType->getSymfonyFormFieldType());
    }

    public function testUsePlaceholder(): void
    {
        $this->assertTrue($this->fieldType->usePlaceholder());
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

        $this->assertCount(4, $operators);
        $this->assertArrayHasKey('=', $operators);
        $this->assertArrayHasKey('!=', $operators);
        $this->assertArrayHasKey('empty', $operators);
        $this->assertArrayHasKey('!empty', $operators);
        $this->assertArrayNotHasKey('in', $operators);
    }

    public function testValueToString(): void
    {
        $fieldValue = new CustomFieldValueOption($this->customField, $this->customItem, 'option_b');

        $this->customField->expects($this->once())
            ->method('valueToLabel')
            ->with('option_b')
            ->willReturn('Option B');

        $this->assertSame('Option B', $this->fieldType->valueToString($fieldValue));
    }

    public function testValueToStringIfOptionDoesNotExist(): void
    {
        $fieldValue = new CustomFieldValueOption($this->customField, $this->customItem, 'unicorn');

        $this->customField->expects($this->once())
            ->method('valueToLabel')
            ->with('unicorn')
            ->will($this->throwException(new NotFoundException('Option unicorn does not exist')));

        $this->assertSame('unicorn', $this->fieldType->valueToString($fieldValue));
    }
}
