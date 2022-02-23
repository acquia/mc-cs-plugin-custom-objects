<?php

declare(strict_types=1);

namespace MauticPlugin\CustomObjectsBundle\Tests\Unit\Form\Type;

use MauticPlugin\CustomObjectsBundle\CustomFieldType\CustomFieldTypeInterface;
use MauticPlugin\CustomObjectsBundle\Entity\CustomField;
use MauticPlugin\CustomObjectsBundle\Entity\CustomFieldValueInterface;
use MauticPlugin\CustomObjectsBundle\Entity\CustomItem;
use MauticPlugin\CustomObjectsBundle\Exception\UndefinedTransformerException;
use MauticPlugin\CustomObjectsBundle\Form\Type\CustomFieldValueType;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CustomFieldValueTypeTest extends \PHPUnit\Framework\TestCase
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
        $this->optionsResolver  = $this->createMock(OptionsResolver::class);
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
            ->with(['data' => 'The default value']) // The default value is set.
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

    public function testConfigureOptions(): void
    {
        $this->optionsResolver->expects($this->once())
            ->method('setDefaults')
            ->with(['data_class' => CustomFieldValueInterface::class]);

        $this->optionsResolver->expects($this->once())
            ->method('setRequired')
            ->with(['customItem']);

        $this->formType->configureOptions($this->optionsResolver);
    }
}
