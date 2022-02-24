<?php

declare(strict_types=1);

namespace MauticPlugin\CustomObjectsBundle\Form\Type\CustomField;

use Mautic\CoreBundle\Form\Type\SortableValueLabelListType;
use MauticPlugin\CustomObjectsBundle\Form\DataTransformer\OptionsTransformer;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;

class OptionsType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add(
                'list',
                CollectionType::class,
                [
                    'label'         => false,
                    'entry_type'    => SortableValueLabelListType::class,
                    'entry_options' => [
                        'label'    => false,
                        'required' => false,
                        'attr'     => [
                            'class'         => 'form-control',
                            'preaddon'      => 'fa fa-times',
                            'preaddon_attr' => [
                                'onclick' => 'Mautic.removeFormListOption(this);',
                            ],
                            'postaddon' => 'fa fa-ellipsis-v handle',
                        ],
                        'error_bubbling' => true,
                    ],
                    'allow_add'      => true,
                    'allow_delete'   => true,
                    'prototype'      => true,
                    'error_bubbling' => false,
                ]
            )
            ->addModelTransformer(new OptionsTransformer());
    }

    /**
     * {@inheritdoc}
     */
    public function buildView(FormView $view, FormInterface $form, array $options): void
    {
        $view->vars['isSortable']     = true;
        $view->vars['addValueButton'] = 'mautic.core.form.list.additem';
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix(): string
    {
        return 'sortablelist';
    }
}
