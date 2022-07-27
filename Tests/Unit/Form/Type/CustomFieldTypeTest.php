<?php

declare(strict_types=1);

namespace MauticPlugin\CustomObjectsBundle\Tests\Unit\Form\Type;

use Mautic\CoreBundle\Form\Type\FormButtonsType;
use Mautic\CoreBundle\Form\Type\YesNoButtonGroupType;
use MauticPlugin\CustomObjectsBundle\Entity\CustomField;
use MauticPlugin\CustomObjectsBundle\Entity\CustomFieldFactory;
use MauticPlugin\CustomObjectsBundle\Form\DataTransformer\CustomObjectHiddenTransformer;
use MauticPlugin\CustomObjectsBundle\Form\DataTransformer\OptionsToStringTransformer;
use MauticPlugin\CustomObjectsBundle\Form\DataTransformer\ParamsToStringTransformer;
use MauticPlugin\CustomObjectsBundle\Form\Type\CustomFieldType;
use MauticPlugin\CustomObjectsBundle\Provider\CustomFieldTypeProvider;
use MauticPlugin\CustomObjectsBundle\Repository\CustomObjectRepository;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;

class CustomFieldTypeTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var mixed|MockObject|FormBuilderInterface
     */
    private $formBuilder;

    /**
     * @var CustomObjectRepository|mixed|MockObject
     */
    private $customObjectRepository;

    /**
     * @var CustomFieldType
     */
    private $formType;

    /**
     * @var CustomField|mixed|MockObject
     */
    private $customField;

    protected function setUp(): void
    {
        parent::setUp();

        $this->formBuilder      = $this->createMock(FormBuilderInterface::class);
        $this->customField      = $this->createMock(CustomField::class);

        $this->customObjectRepository     = $this->createMock(CustomObjectRepository::class);
        $customFieldTypeProvider          = $this->createMock(CustomFieldTypeProvider::class);
        $paramsToStringTransformer        = $this->createMock(ParamsToStringTransformer::class);
        $optionsToStringTransformer       = $this->createMock(OptionsToStringTransformer::class);
        $customFieldFactory               = $this->createMock(CustomFieldFactory::class);
        $this->formType                   = new CustomFieldType(
            $this->customObjectRepository,
            $customFieldTypeProvider,
            $paramsToStringTransformer,
            $optionsToStringTransformer,
            $customFieldFactory
        );
    }

    public function testBuildModalFormFields(): void
    {
        $options['data']               = $this->customField;
        $options['custom_object_form'] = '';

        $this->formBuilder->expects($this->once())
            ->method('addModelTransFormer')
            ->with(new CustomObjectHiddenTransformer($this->customObjectRepository));

        $this->formBuilder->expects($this->exactly(11))
            ->method('add')
            ->withConsecutive(
                [
                    'id', HiddenType::class,
                ],
                [
                    'customObject',
                    HiddenType::class,
                    [
                        'required' => true,
                    ],
                ],
                [
                    'isPublished',
                    HiddenType::class,
                    [
                        'required' => true,
                    ],
                ],
                [
                    'type',
                    HiddenType::class,
                    [
                        'required' => true,
                    ],
                ],
                [
                    'order',
                    HiddenType::class,
                ],
                [
                    'label',
                    TextType::class,
                    [
                        'label'      => 'custom.field.label.label',
                        'required'   => true,
                        'label_attr' => ['class' => 'control-label'],
                        'attr'       => [
                            'class' => 'form-control',
                        ],
                    ],
                ],
                [
                    'alias',
                    TextType::class,
                    [
                        'label'      => 'custom.field.alias.label',
                        'required'   => false,
                        'label_attr' => ['class' => 'control-label'],
                        'attr'       => [
                            'class'   => 'form-control',
                            'tooltip' => 'custom.field.help.alias',
                        ],
                    ],
                ],
                [
                    'required',
                    YesNoButtonGroupType::class,
                    [
                        'label' => 'custom.field.label.required',
                        'attr'  => [
                            // Need for JS call of method used for changing values dynamically in the frontend. Check co-form.js
                            'data-toggle-button' => true,
                            'readonly'           => false,
                        ],
                    ],
                ],
                [
                    'showInCustomObjectDetailList',
                    YesNoButtonGroupType::class,
                    [
                        'label' => 'custom.field.show_in_custom_object_detail_list.label',
                    ],
                ],
                [
                    'showInContactDetailList',
                    YesNoButtonGroupType::class,
                    [
                        'label' => 'custom.field.show_in_contact_detail_list.label',
                    ],
                ],
                [
                    'buttons',
                    FormButtonsType::class,
                    [
                        'apply_text'     => '',
                        'cancel_onclick' => "mQuery('form[name=custom_field]').attr('method', 'get').attr('action', mQuery('form[name=custom_field]').attr('action').replace('/save', '/cancel'));",
                    ],
                ]
            );

        $this->formBuilder->expects($this->once())
            ->method('get')
            ->with('customObject')
            ->willReturn($this->formBuilder);
        $this->formBuilder->expects($this->once())
            ->method('addModelTransformer');

        $this->formType->buildForm($this->formBuilder, $options);
    }
}
