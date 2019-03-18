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
use MauticPlugin\CustomObjectsBundle\Form\CustomObjectHiddenTransformer;
use MauticPlugin\CustomObjectsBundle\Form\DataTransformer\OptionsToStringTransformer;
use MauticPlugin\CustomObjectsBundle\Form\DataTransformer\ParamsToStringTransformer;
use MauticPlugin\CustomObjectsBundle\Form\Type\CustomField\OptionType;
use MauticPlugin\CustomObjectsBundle\Form\Type\CustomField\ParamsType;
use MauticPlugin\CustomObjectsBundle\Provider\CustomFieldTypeProvider;
use MauticPlugin\CustomObjectsBundle\Repository\CustomObjectRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use MauticPlugin\CustomObjectsBundle\Entity\CustomField;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
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
     * @param CustomObjectRepository     $customObjectRepository
     * @param CustomFieldTypeProvider    $customFieldTypeProvider
     * @param ParamsToStringTransformer  $paramsToStringTransformer
     * @param OptionsToStringTransformer $optionsToStringTransformer
     */
    public function __construct(
        CustomObjectRepository $customObjectRepository,
        CustomFieldTypeProvider $customFieldTypeProvider,
        ParamsToStringTransformer $paramsToStringTransformer,
        OptionsToStringTransformer $optionsToStringTransformer
    ) {
        $this->customObjectRepository     = $customObjectRepository;
        $this->customFieldTypeProvider    = $customFieldTypeProvider;
        $this->paramsToStringTransformer  = $paramsToStringTransformer;
        $this->optionsToStringTransformer = $optionsToStringTransformer;
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
    public function buildModalFormFields(FormBuilderInterface $builder, array $options): void
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

            $form->add(
                'params',
                ParamsType::class,
                [
                    'customField' => $customField,
                ]
            );

            if ($customField->getTypeObject()->hasChoices()) {
                $form->add(
                    'options',
                    CollectionType::class,
                    [
                        'mapped'       => false,
                        'allow_add'    => true,
                        'allow_delete' => true,
                        'delete_empty' => true,
                        'entry_type'   => OptionType::class,
                        'prototype'    => true,
                    ]
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

            if (empty($customField)) {
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

        $builder->add(
            $builder->create(
                'options',
                HiddenType::class
            )
                ->addModelTransformer($this->optionsToStringTransformer)
        );

        // Possibility to mark field as deleted in POST data
        $builder->add('deleted', HiddenType::class, ['mapped' => false]);
    }
}
