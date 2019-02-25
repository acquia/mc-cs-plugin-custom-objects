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
use Symfony\Component\Form\Extension\Core\Type\TextType;
use MauticPlugin\CustomObjectsBundle\Entity\CustomItem;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Mautic\CoreBundle\Form\Type\FormButtonsType;
use Mautic\CoreBundle\Form\Type\YesNoButtonGroupType;
use Mautic\CategoryBundle\Form\Type\CategoryListType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;

class CustomItemType extends AbstractType
{   
    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add(
            'name',
            TextType::class,
            [
                'label'      => 'custom.item.name.label',
                'required'   => true,
                'label_attr' => ['class' => 'control-label'],
                'attr'       => ['class' => 'form-control'],
            ]
        );

        $builder->add(
            'custom_field_values',
            CollectionType::class,
            [
                'entry_type'    => CustomFieldValueType::class,
                'entry_options' => [
                    'label'      => false,
                    'customItem' => $builder->getData(),
                ],
                'label'      => false,
                'required'   => false,
            ]
        );

        $builder->add('category', CategoryListType::class, ['bundle' => 'global']);
        $builder->add('isPublished', YesNoButtonGroupType::class);

        $builder->add(
            'buttons',
            FormButtonsType::class,
            [
                'cancel_onclick' => "mQuery('form[name=custom_item]').attr('method', 'get').attr('action', mQuery('form[name=custom_item]').attr('action').replace('/save', '/cancel'));",
            ]
        );

        $builder->setAction($options['action']);
    }

    /**
     * {@inheritdoc}
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(['data_class' => CustomItem::class]);
        $resolver->setRequired(['objectId']);
    }
}
