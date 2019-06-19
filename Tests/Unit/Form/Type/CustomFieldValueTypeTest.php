<?php

declare(strict_types=1);

/*
 * @copyright   2019 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc
 *
 * @link        https://mautic.com
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\CustomObjectsBundle\Tests\Unit\Form\Type;

use MauticPlugin\CustomObjectsBundle\CustomFieldType\CustomFieldTypeInterface;
use MauticPlugin\CustomObjectsBundle\Entity\CustomField;
use MauticPlugin\CustomObjectsBundle\Entity\CustomFieldValueInterface;
use MauticPlugin\CustomObjectsBundle\Entity\CustomItem;
use MauticPlugin\CustomObjectsBundle\Form\Type\CustomFieldValueType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Form\DataTransformerInterface;
use MauticPlugin\CustomObjectsBundle\Exception\UndefinedTransformerException;

class CustomFieldValueTypeTest extends \PHPUnit_Framework_TestCase
{
    private $formBuilder;
    private $optionsResolver;
    private $customItem;
    private $customFieldValue;
    private $customField;
    private $customFieldType;

    /**
     * @var CustomFieldValueType
     */
    private $formType;

    protected function setUp(): void
    {
        parent::setUp();

        $this->formBuilder      = $this->createMock(FormBuilderInterface::class);
        $this->optionsResolver  = $this->createMock(OptionsResolverInterface::class);
        $this->customItem       = $this->createMock(CustomItem::class);
        $this->customFieldValue = $this->createMock(CustomFieldValueInterface::class);
        $this->customField      = $this->createMock(CustomField::class);
        $this->customFieldType  = $this->createMock(CustomFieldTypeInterface::class);
        $this->formType         = new CustomFieldValueType();
    }

    public function testBuildFormForNewItem(): void
    {
        $options = ['customItem' => $this->customItem];

        $this->formBuilder->expects($this->once())
            ->method('getName')
            ->willReturn(123);

        $this->customItem->expects($this->once())
            ->method('findCustomFieldValueForFieldId')
            ->with(123)
            ->willReturn($this->customFieldValue);

        $this->customFieldValue->expects($this->once())
            ->method('getCustomField')
            ->willReturn($this->customField);

        $this->customField->method('getTypeObject')
            ->willReturn($this->customFieldType);

        $this->customFieldType->expects($this->once())
            ->method('getSymfonyFormFieldType')
            ->willReturn(TextType::class);

        $this->customField->expects($this->once())
            ->method('getDefaultValue')
            ->willReturn('The default value');

        $this->customField->expects($this->once())
            ->method('getFormFieldOptions')
            ->with(['empty_data' => 'The default value']) // The default value is set.
            ->willReturn(['the' => 'options']);

        $this->formBuilder->expects($this->once())
            ->method('create')
            ->with(
                'value',
                TextType::class,
                ['the' => 'options']
            )
            ->willReturnSelf();

        $viewTransformer = $this->createMock(DataTransformerInterface::class);

        $this->customFieldType->expects($this->once())
            ->method('createViewTransformer')
            ->willReturn($viewTransformer);

        $this->formBuilder->expects($this->once())
            ->method('addViewTransformer')
            ->with($viewTransformer);

        $this->formBuilder->expects($this->once())
            ->method('add')
            ->with($this->formBuilder);

        $this->formType->buildForm($this->formBuilder, $options);
    }

    public function testBuildFormForExistingItem(): void
    {
        $options = ['customItem' => $this->customItem];

        $this->formBuilder->expects($this->once())
            ->method('getName')
            ->willReturn(123);

        $this->customItem->expects($this->once())
            ->method('findCustomFieldValueForFieldId')
            ->with(123)
            ->willReturn($this->customFieldValue);

        $this->customItem->expects($this->once())
            ->method('getId')
            ->willReturn(456);

        $this->customFieldValue->expects($this->once())
            ->method('getCustomField')
            ->willReturn($this->customField);

        $this->customField->method('getTypeObject')
            ->willReturn($this->customFieldType);

        $this->customFieldType->expects($this->once())
            ->method('getSymfonyFormFieldType')
            ->willReturn(TextType::class);

        $this->customField->expects($this->never())
            ->method('getDefaultValue');

        $this->customField->expects($this->once())
            ->method('getFormFieldOptions')
            ->with([]) // The default value is not set.
            ->willReturn(['the' => 'options']);

        $this->customFieldType->expects($this->once())
            ->method('createViewTransformer')
            ->will($this->throwException(new UndefinedTransformerException()));

        $this->formBuilder->expects($this->never())
            ->method('addViewTransformer');

        $this->formBuilder->expects($this->once())
            ->method('create')
            ->with(
                'value',
                TextType::class,
                ['the' => 'options']
            )
            ->willReturnSelf();

        $this->formBuilder->expects($this->once())
            ->method('add')
            ->with($this->formBuilder);

        $this->formType->buildForm($this->formBuilder, $options);
    }

    public function testSetDefaultOptions(): void
    {
        $this->optionsResolver->expects($this->once())
            ->method('setDefaults')
            ->with(['data_class' => CustomFieldValueInterface::class]);

        $this->optionsResolver->expects($this->once())
            ->method('setRequired')
            ->with(['customItem']);

        $this->formType->setDefaultOptions($this->optionsResolver);
    }
}
