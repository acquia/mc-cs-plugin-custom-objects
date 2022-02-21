<?php

declare(strict_types=1);

namespace MauticPlugin\CustomObjectsBundle\Form\Type\CustomField;

use MauticPlugin\CustomObjectsBundle\Entity\CustomField\Params;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ParamsType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        if ($options['use_placeholder']) {
            $builder->add(
                'placeholder',
                TextType::class,
                [
                    'label'      => 'custom.field.label.placeholder',
                    'required'   => false,
                    'attr'       => [
                        'class'    => 'form-control',
                        'tooltip'  => 'custom.field.help.placeholder',
                    ],
                ]
            );
        }
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(
            [
                'label'              => false,
                'data_class'         => Params::class,
                'custom_object_form' => false,
                'csrf_protection'    => false,
                'has_choices'        => false,
                'use_placeholder'    => false, // @see \MauticPlugin\CustomObjectsBundle\CustomFieldType\AbstractCustomFieldType::usePlaceholder()
            ]
        );
    }
}
