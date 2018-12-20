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

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use MauticPlugin\CustomObjectsBundle\Entity\CustomField;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Mautic\CoreBundle\Form\Type\FormButtonsType;
use Mautic\CoreBundle\Form\Type\YesNoButtonGroupType;
use Mautic\CategoryBundle\Form\Type\CategoryListType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use MauticPlugin\CustomObjectsBundle\Model\CustomObjectModel;

class CustomFieldType extends AbstractType
{
    /**
     * @var CustomObjectModel
     */
    private $customObjectModel;

    /**
     * @param CustomObjectModel $customObjectModel
     */
    public function __construct(CustomObjectModel $customObjectModel)
    {
        $this->customObjectModel = $customObjectModel;
    }

    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
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
            'alias',
            TextType::class,
            [
                'label'      => 'custom.field.alias.label',
                'required'   => true,
                'label_attr' => ['class' => 'control-label'],
                'attr'       => [
                    'class' => 'form-control',
                ],
            ]
        );

        $builder->add(
            'type',
            ChoiceType::class,
            [
                'label'      => 'custom.field.type.label',
                'required'   => false,
                'label_attr' => ['class' => 'control-label'],
                'choices'    => ['text'],
                'attr'       => [
                    'class' => 'form-control',
                ],
            ]
        );

        $builder->add(
            'customObject',
            ChoiceType::class,
            [
                'label'             => 'custom.field.object.label',
                'required'          => false,
                'label_attr'        => ['class' => 'control-label'],
                'attr'              => ['class' => 'form-control'],
                'choices_as_values' => true,
                'choices'           => $this->customObjectModel->getEntities(['ignore_paginator' => true]),
                'choice_label'      => function($customObject) {
                    return $customObject->getName();
                },
            ]
        );

        $builder->add('isPublished', YesNoButtonGroupType::class);

        $builder->add(
            'buttons',
            FormButtonsType::class,
            [
                'cancel_onclick' => "mQuery('form[name=custom_field]').attr('method', 'get').attr('action', mQuery('form[name=custom_field]').attr('action').replace('/save', '/cancel'));",
            ]
        );

        $builder->setAction($options['action']);
    }

    /**
     * {@inheritdoc}
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(
            [
                'data_class' => CustomField::class,
            ]
        );
    }
}
