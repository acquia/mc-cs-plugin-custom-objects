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

use MauticPlugin\CustomObjectsBundle\Form\CustomObjectHiddenTransformer;
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
     * @param CustomObjectRepository $customObjectRepository
     */
    public function __construct(CustomObjectRepository $customObjectRepository)
    {
        $this->customObjectRepository = $customObjectRepository;
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
            ]
        );
    }

    /**
     * Build fields for form in modal. Full specification of custom field.
     *
     * @param FormBuilderInterface $builder
     * @param mixedp[]             $options
     */
    public function buildModalFormFields(FormBuilderInterface $builder, array $options): void
    {
        $builder->add(
            'required',
            \Symfony\Component\Form\Extension\Core\Type\CheckboxType::class,
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
                $customField->getTypeObject()->getSymfonyFormFiledType(),
                [
                    'label'    => 'custom.field.label.default_value',
                    'required' => false,
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
        $builder->add('required', HiddenType::class);
        $builder->add('defaultValue', HiddenType::class);

        // Possibility to mark field as deleted in POST data
        $builder->add('deleted', HiddenType::class, ['mapped' => false]);
    }
}
