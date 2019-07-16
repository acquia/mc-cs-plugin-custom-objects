<?php

declare(strict_types=1);

/*
* @copyright   2019 Mautic, Inc. All rights reserved
* @author      Mautic, Inc.
*
* @link        https://mautic.com
*
* @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
*/

namespace MauticPlugin\CustomObjectsBundle\Form\Type\CustomField;

use MauticPlugin\CustomObjectsBundle\Entity\CustomField\Params;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class ParamsType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add(
            'requiredValidationMessage',
            TextType::class,
            [
                'label'      => 'custom.field.label.required_validation_message',
                'required'   => false,
                'attr'       => [
                    'class'    => 'form-control',
                ],
            ]
        );

        if ($options['use_placeholder']) {
            $builder->add(
                'placeholder',
                TextType::class,
                [
                    'label'      => 'custom.field.label.placeholder',
                    'required'   => false,
                    'attr'       => [
                        'class'    => 'form-control',
                    ],
                ]
            );
        }
    }

    /**
     * {@inheritdoc}
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver): void
    {
        $resolver->setDefaults(
            [
                'data_class'         => Params::class,
                'custom_object_form' => false,
                'csrf_protection'    => false,
                'has_choices'        => false,
                'use_placeholder'    => false, // @see \MauticPlugin\CustomObjectsBundle\CustomFieldType\AbstractCustomFieldType::usePlaceholder()
            ]
        );
    }
}
