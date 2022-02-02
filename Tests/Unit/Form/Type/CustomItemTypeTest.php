<?php

namespace MauticPlugin\CustomObjectsBundle\Tests\Unit\Form\Type;

use Mautic\CategoryBundle\Form\Type\CategoryListType;
use Mautic\CoreBundle\Form\Type\FormButtonsType;
use Mautic\CoreBundle\Form\Type\YesNoButtonGroupType;
use MauticPlugin\CustomObjectsBundle\Entity\CustomItem;
use MauticPlugin\CustomObjectsBundle\Entity\CustomObject;
use MauticPlugin\CustomObjectsBundle\Form\Type\CustomFieldValueType;
use MauticPlugin\CustomObjectsBundle\Form\Type\CustomItemType;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Valid;

class CustomItemTypeTest extends TestCase
{
    /** @var CustomItemType */
    private $customItemType;

    /** @var FormBuilderInterface|MockObject */
    private $builder;

    /** @var OptionsResolver|MockObject */
    private $optionsResolver;

    protected function setUp(): void
    {
        $this->customItemType  = new CustomItemType();
        $this->builder         = $this->createMock(FormBuilderInterface::class);
        $this->optionsResolver = $this->createMock(OptionsResolver::class);
    }

    public function testBuildForm(): void
    {
        $customObject    = new CustomObject();
        $customItem      = new CustomItem($customObject);
        $childCustomItem = new CustomItem($customObject);
        $customItem->setChildCustomItem($childCustomItem);

        $options = [
            'cancelUrl' => 'https://mautic/cancel/url',
            'action'    => '/form/action',
        ];

        $this
            ->builder
            ->expects($this->once())
            ->method('getData')
            ->willReturn($customItem);

        $cancelOnclickUrl = "mQuery('form[name=custom_item]').attr('action', \"https:\/\/mautic\/cancel\/url\");";
        $this
            ->builder
            ->expects($this->exactly(7))
            ->method('add')
            ->withConsecutive(
                [
                    'name',
                    TextType::class,
                    [
                        'label'      => 'custom.item.name.label',
                        'required'   => true,
                        'label_attr' => ['class' => 'control-label'],
                        'attr'       => ['class' => 'form-control'],
                    ],
                ],
                [
                    'custom_field_values',
                    CollectionType::class,
                    [
                        'entry_type'    => CustomFieldValueType::class,
                        'label'         => false,
                        'constraints'   => [new Valid()],
                        'entry_options' => [
                            'label'      => false,
                            'customItem' => $customItem,
                        ],
                    ],
                ],
                [
                    'child_custom_field_values',
                    CollectionType::class,
                    [
                        'entry_type'    => CustomFieldValueType::class,
                        'label'         => false,
                        'constraints'   => [new Valid()],
                        'entry_options' => [
                            'label'      => false,
                            'customItem' => $customItem->getChildCustomItem(),
                        ],
                    ],
                ],
                [
                    'contact_id',
                    HiddenType::class,
                    [
                        'mapped' => false,
                        'data'   => null,
                    ],
                ],
                [
                    'category',
                    CategoryListType::class,
                    ['bundle' => 'global'],
                ],
                [
                    'isPublished',
                    YesNoButtonGroupType::class,
                ],
                [
                    'buttons',
                    FormButtonsType::class,
                    [
                        'cancel_onclick' => $cancelOnclickUrl,
                    ],
                ]
            );

        $this
            ->builder
            ->expects($this->once())
            ->method('setAction')
            ->with($options['action']);

        $this->customItemType->buildForm($this->builder, $options);
    }

    public function testConfigureOptions(): void
    {
        $this
            ->optionsResolver
            ->expects($this->once())
            ->method('setDefaults')
            ->with([
                'data_class' => CustomItem::class,
            ]);

        $this
            ->optionsResolver
            ->expects($this->once())
            ->method('setRequired')
            ->with(['objectId']);

        $this
            ->optionsResolver
            ->expects($this->once())
            ->method('setDefined')
            ->with(['contactId', 'cancelUrl']);

        $this->customItemType->configureOptions($this->optionsResolver);
    }
}
