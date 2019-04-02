<?php

declare(strict_types=1);

/*
 * @copyright   2018 Mautic, Inc. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.com
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\CustomObjectsBundle\Form\Type;

use Mautic\CoreBundle\Form\Type\YesNoButtonGroupType;
use MauticPlugin\CustomObjectsBundle\CustomFieldType\CountryListType;
use MauticPlugin\CustomObjectsBundle\CustomFieldType\DateTimeType;
use MauticPlugin\CustomObjectsBundle\CustomFieldType\DateType;
use MauticPlugin\CustomObjectsBundle\Entity\CustomFieldFactory;
use MauticPlugin\CustomObjectsBundle\Form\DataTransformer\CustomObjectHiddenTransformer;
use MauticPlugin\CustomObjectsBundle\Form\DataTransformer\OptionsToStringTransformer;
use MauticPlugin\CustomObjectsBundle\Form\DataTransformer\ParamsToStringTransformer;
use MauticPlugin\CustomObjectsBundle\Form\Type\CustomField\OptionsType;
use MauticPlugin\CustomObjectsBundle\Form\Type\CustomField\ParamsType;
use MauticPlugin\CustomObjectsBundle\Provider\CustomFieldTypeProvider;
use MauticPlugin\CustomObjectsBundle\Repository\CustomObjectRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use MauticPlugin\CustomObjectsBundle\Entity\CustomField;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Mautic\CoreBundle\Form\Type\FormButtonsType;

class CustomFieldType extends AbstractType
{
    /**
     * @var CustomObjectRepository
     */
    private $customObjectRepository;

    /**
     * @var CustomFieldTypeProvider
     */
    private $customFieldTypeProvider;

    /**
     * @var ParamsToStringTransformer
     */
    private $paramsToStringTransformer;

    /**
     * @var OptionsToStringTransformer
     */
    private $optionsToStringTransformer;

    /**
     * @var CustomFieldFactory
     */
    private $customFieldFactory;

    /**
     * @param CustomObjectRepository     $customObjectRepository
     * @param CustomFieldTypeProvider    $customFieldTypeProvider
     * @param ParamsToStringTransformer  $paramsToStringTransformer
     * @param OptionsToStringTransformer $optionsToStringTransformer
     * @param CustomFieldFactory         $customFieldFactory
     */
    public function __construct(
        CustomObjectRepository $customObjectRepository,
        CustomFieldTypeProvider $customFieldTypeProvider,
        ParamsToStringTransformer $paramsToStringTransformer,
        OptionsToStringTransformer $optionsToStringTransformer,
        CustomFieldFactory $customFieldFactory
    ) {
        $this->customObjectRepository     = $customObjectRepository;
        $this->customFieldTypeProvider    = $customFieldTypeProvider;
        $this->paramsToStringTransformer  = $paramsToStringTransformer;
        $this->optionsToStringTransformer = $optionsToStringTransformer;
        $this->customFieldFactory         = $customFieldFactory;
    }

    /**
     * @param FormBuilderInterface $builder
     * @param mixed[]              $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        // Is part of custom object form?
        $isCustomObjectForm = !empty($options['custom_object_form']);

        $builder->add('id', HiddenType::class);

        $builder->add(
            'customObject',
            HiddenType::class,
            [
                'required' => true,
            ]
        );

        $builder
            ->get('customObject')
            ->addModelTransformer(new CustomObjectHiddenTransformer($this->customObjectRepository));

        $builder->add(
            'isPublished',
            HiddenType::class,
            [
                'required' => true,
            ]
        );

        $builder->add(
            'type',
            HiddenType::class,
            [
                'required' => true,
            ]
        );

        $builder->add(
            'order',
            HiddenType::class
        );

        if ($isCustomObjectForm) {
            $this->buildPanelFormFields($builder);
        } else {
            $this->buildModalFormFields($builder, $options);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver): void
    {
        $resolver->setDefaults(
            [
                'data_class'         => CustomField::class,
                'empty_data'         => function (FormInterface $form) {
                    $type = $form->get('type')->getData();
                    $customObject = $form->get('customObject')->getData();

                    return $this->customFieldFactory->create($type, $customObject);
                },
                'custom_object_form' => false, // Is form used as subform?
                'csrf_protection'    => false,
                'allow_extra_fields' => true,
            ]
        );
    }

    /**
     * Build fields for form in modal. Full specification of custom field.
     *
     * @param FormBuilderInterface $builder
     * @param mixed[]              $options
     */
    private function buildModalFormFields(FormBuilderInterface $builder, array $options): void
    {
        $builder->add(
            'label',
            TextType::class,
            [
                'label'      => 'custom.field.label.label',
                'required'   => true,
                'label_attr' => ['class' => 'control-label'],
                'attr'       => [
                    'class' => 'form-control',
                ],
            ]
        );

        $builder->add(
            'required',
            YesNoButtonGroupType::class,
            [
                'label' => 'custom.field.label.required',
            ]
        );

        $builder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event): void {
            /** @var CustomField $customField */
            $customField = $event->getData();
            $form = $event->getForm();
            $hasChoices = $customField->getTypeObject()->hasChoices();

            $this->createDefaultValueInputForModal($form, $customField);

            $form->add(
                'params',
                ParamsType::class,
                [
                    'has_choices'     => $hasChoices,
                    'use_empty_value' => $customField->getTypeObject()->useEmptyValue(),
                ]
            );

            if ($hasChoices) {
                $form->add(
                    'options',
                    OptionsType::class
                );
            }
        });

        $builder->add(
            'buttons',
            FormButtonsType::class,
            [
                'apply_text'     => '',
                'cancel_onclick' => "mQuery('form[name=custom_field]').attr('method', 'get').attr('action', mQuery('form[name=custom_field]').attr('action').replace('/save', '/cancel'));",
            ]
        );

        $builder->setAction($options['action']);
    }

    /**
     * Build fields for panel - custom field list.
     * All should be hidden.
     *
     * @param FormBuilderInterface $builder
     */
    private function buildPanelFormFields(FormBuilderInterface $builder): void
    {
        $builder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event): void {
            /** @var CustomField $customField */
            $customField = $event->getData();
            $builder = $event->getForm();

            if (!$customField) {
                // Custom field is new without data fetched from DB
                return;
            }

            $builder->add(
                'field',
                $customField->getTypeObject()->getSymfonyFormFieldType(),
                $customField->getFormFieldOptions(
                    [
                        'mapped'     => false,
                        'required'   => false,
                        'data'       => $customField->getDefaultValue(),
                        'attr'       => [
                            'readonly' => true,
                        ],
                    ]
                )
            );

            if ($customField->getDefaultValue() instanceof \DateTime) {
                // @todo default value needs to be string because of DB column type
                // @see CustomObjectsBundle/EventListener/CustomFieldPostLoadSubscriber.php:71
                $customField->setDefaultValue(
                    $customField->getDefaultValue()->format('Y-m-d H:i:s')
                );
            }

            $builder->add('defaultValue', HiddenType::class);
        });

        $builder->addEventListener(FormEvents::SUBMIT, function (FormEvent $event): void {
            // Set proper type object when creating new custom field
            /** @var CustomField $customField */
            $customField = $event->getData();

            if (!$customField->getTypeObject() && $customField->getType()) {
                $customField->setTypeObject($this->customFieldTypeProvider->getType($customField->getType()));
            }
        });

        $builder->add('label', HiddenType::class);
        $builder->add('required', HiddenType::class);

        $builder->add(
            $builder->create(
                'params',
                HiddenType::class
            )
                ->addModelTransformer($this->paramsToStringTransformer)
        );

        // @todo This breaks saving of CO
        $builder->add(
            $builder->create(
                'options',
                HiddenType::class
            )
                ->addModelTransformer($this->optionsToStringTransformer)
        );

        // Possibility to mark field as deleted in POST data
        $builder->add('deleted', HiddenType::class, ['mapped' => false]);

        $this->fixValidationBeforeSubmit($builder);
    }

    /**
     * Fix possible collision of value with validator for custom field demo in panel.
     *
     * @param FormBuilderInterface $builder
     */
    private function fixValidationBeforeSubmit(FormBuilderInterface $builder): void
    {
        $builder->addEventListener(
            FormEvents::PRE_SUBMIT,
            function (FormEvent $event): void {
                $form = $event->getForm();

                // Fix invalid value for integer field
                if ($form->has('field')) {
                    $form->remove('field');
                    $form->add(
                        'field',
                        'text',
                        [
                            'required' => false,
                            'mapped'   => false,
                        ]
                    );
                }
            }
        );
    }

    /**
     * Create default value input for modal.
     * Dynamically options managed in modal are not supported so for them leave default value input as text for now.
     *
     * @param FormInterface $form
     * @param CustomField   $customField
     */
    private function createDefaultValueInputForModal(FormInterface $form, CustomField $customField): void
    {
        $handledTypes = [
            CountryListType::class,
            DateType::class,
            DateTimeType::class,
        ];

        if (in_array(get_class($customField->getTypeObject()), $handledTypes, true)) {
            $form->add(
                'defaultValue',
                $customField->getTypeObject()->getSymfonyFormFieldType(),
                $customField->getTypeObject()->createFormTypeOptions(
                    [
                        'label'      => 'custom.field.label.default_value',
                        'required'   => false,
                        'attr'       => [
                            'class' => 'form-control',
                        ],
                    ]
                )
            );
        } else {
            $form->add(
                'defaultValue',
                TextType::class,
                [
                    'label'      => 'custom.field.label.default_value',
                    'required'   => false,
                    'attr'       => [
                        'class' => 'form-control',
                    ],
                ]
            );
        }
    }
}
