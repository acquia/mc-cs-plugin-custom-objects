<?php

/*
* @copyright   2019 Mautic, Inc. All rights reserved
* @author      Mautic, Inc.
*
* @link        https://mautic.com
*
* @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
*/

namespace MauticPlugin\CustomObjectsBundle\Form\Type\CustomField;

use Mautic\CoreBundle\Form\Type\SortableValueLabelListType;
use MauticPlugin\CustomObjectsBundle\Form\DataTransformer\OptionsTransformer;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class OptionsType extends CollectionType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        parent::buildForm($builder, $options);
        $builder->addModelTransformer(new OptionsTransformer());
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        parent::configureOptions($resolver);
        $options = $resolver->resolve();

        $options = array_merge_recursive(
            $options,
            [
                'label'      => false,
                'entry_type' => SortableValueLabelListType::class,
                'options'    => [
                    'label'    => false,
                    'required' => false,
                    'attr'     => [
                        'class'         => 'form-control',
                        'preaddon'      => true,
                        'preaddon_attr' => [
                            'onclick' => true,
                        ],
                        'postaddon' => true,
                    ],
                    'error_bubbling' => true,
                ],
                'allow_add'      => true,
                'allow_delete'   => true,
                'prototype'      => true,
                'error_bubbling' => false,
            ]
        );

        return $options;
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix(): string
    {
        return $this->getName();
    }

    /**
     * {@inheritdoc}
     */
    public function getName(): string
    {
        return 'sortablelist';
    }
}