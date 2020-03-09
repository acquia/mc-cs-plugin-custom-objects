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

use Mautic\LeadBundle\Provider\FilterOperatorProviderInterface;
use MauticPlugin\CustomObjectsBundle\CustomFieldType\SelectType;
use MauticPlugin\CustomObjectsBundle\Entity\CustomField;
use MauticPlugin\CustomObjectsBundle\Entity\CustomFieldValueOption;
use MauticPlugin\CustomObjectsBundle\Entity\CustomItem;
use MauticPlugin\CustomObjectsBundle\Exception\NotFoundException;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Translation\TranslatorInterface;

class SelectTypeTest extends \PHPUnit\Framework\TestCase
{
    private $translator;
    private $customField;
    private $customItem;

    /**
     * @var SelectType
     */
    private $fieldType;

    protected function setUp(): void
    {
        parent::setUp();

        $this->translator  = $this->createMock(TranslatorInterface::class);
        $this->customField = $this->createMock(CustomField::class);
        $this->customItem  = $this->createMock(CustomItem::class);
        $this->fieldType   = new SelectType(
            $this->translator,
            $this->createMock(FilterOperatorProviderInterface::class)
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
