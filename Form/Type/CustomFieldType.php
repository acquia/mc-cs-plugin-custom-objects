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
use MauticPlugin\CustomObjectsBundle\Form\DataTransformer\ParamsToStringTransformer;
use MauticPlugin\CustomObjectsBundle\Provider\CustomFieldTypeProvider;
use MauticPlugin\CustomObjectsBundle\Repository\CustomObjectRepository;
use Symfony\Component\Form\AbstractType;
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
     * @param CustomObjectRepository    $customObjectRepository
     * @param CustomFieldTypeProvider   $customFieldTypeProvider
     * @param ParamsToStringTransformer $paramsToStringTransformer
     */
    public function __construct(
        CustomObjectRepository $customObjectRepository,
        CustomFieldTypeProvider $customFieldTypeProvider,
        ParamsToStringTransformer $paramsToStringTransformer
    ) {
        $this->customObjectRepository    = $customObjectRepository;
        $this->customFieldTypeProvider   = $customFieldTypeProvider;
        $this->paramsToStringTransformer = $paramsToStringTransformer;
    }

    /**
     * @param FormBuilderInterface $builder
     * @param mixed[]              $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        // Is part of custom object form?
        $customObjectForm = !empty($options['custom_object_form']);

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

        if ($customObjectForm) {
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
            'params',
            CustomFieldParamsType::class
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
                [
                    'label'      => 'custom.field.label.default_value',
                    'required'   => false,
                    'attr'       => [
                        'class' => 'form-control',
                    ],
                ]
            );
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
                [
                    'mapped'     => false,
                    'label'      => $customField->getLabel(),
                    'required'   => false,
                    'data'       => $customField->getDefaultValue(),
                    'label_attr' => ['class' => 'control-label'],
                    'attr'       => [ // @todo this overrides configureOptions() method content
                        'class'    => 'form-control',
                        'readonly' => true,
                    ],
                ]
            );
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
        $builder->add('defaultValue', HiddenType::class);

        $builder->add(
            $builder->create(
                'params',
                HiddenType::class,
                [
                ]
            )
                ->addModelTransformer($this->paramsToStringTransformer)
        );

        // Possibility to mark field as deleted in POST data
        $builder->add('deleted', HiddenType::class, ['mapped' => false]);
    }
}
