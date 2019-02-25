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

use MauticPlugin\CustomObjectsBundle\Entity\CustomField;
use MauticPlugin\CustomObjectsBundle\Provider\CustomFieldTypeProvider;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use MauticPlugin\CustomObjectsBundle\Entity\CustomObject;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Mautic\CoreBundle\Form\Type\FormButtonsType;
use Mautic\CoreBundle\Form\Type\YesNoButtonGroupType;
use Mautic\CategoryBundle\Form\Type\CategoryListType;

class CustomObjectType extends AbstractType
{
    /**
     * @var CustomFieldTypeProvider
     */
    private $customFieldTypeProvider;

    /**
     * CustomObjectType constructor.
     *
     * @param CustomFieldTypeProvider $customFieldTypeProvider
     */
    public function __construct(CustomFieldTypeProvider $customFieldTypeProvider)
    {
        $this->customFieldTypeProvider = $customFieldTypeProvider;
    }

    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add(
            'namePlural',
            TextType::class,
            [
                'label'      => 'custom.object.name.plural.label',
                'required'   => true,
                'label_attr' => ['class' => 'control-label'],
                'attr'       => [
                    'class' => 'form-control',
                ],
            ]
        );

        $builder->add(
            'nameSingular',
            TextType::class,
            [
                'label'      => 'custom.object.name.singular.label',
                'required'   => true,
                'label_attr' => ['class' => 'control-label'],
                'attr'       => [
                    'class' => 'form-control',
                ],
            ]
        );

        $builder->add(
            'description',
            TextareaType::class,
            [
                'label'      => 'custom.object.description.label',
                'required'   => false,
                'label_attr' => ['class' => 'control-label'],
                'attr'       => [
                    'class' => 'form-control',
                ],
            ]
        );

        $builder->add('category', CategoryListType::class, ['bundle' => 'global']);
        $builder->add('isPublished', YesNoButtonGroupType::class);

        $builder->add('customFields',
            CollectionType::class,
            [
                'entry_type'         => CustomFieldType::class,
                'entry_options'      => ['custom_object_form' => true],
                'allow_extra_fields' => true,
                'allow_add'          => true,
                'allow_delete'       => true,
                'by_reference'       => false,
                'prototype'          => false, // Do not use CF panel prototype in DOM
            ]
        );

        $builder->add(
            'buttons',
            FormButtonsType::class,
            [
                'cancel_onclick' => "mQuery('form[name=custom_object]').attr('method', 'get').attr('action', mQuery('form[name=custom_object]').attr('action').replace('/save', '/cancel'));",
            ]
        );

        $builder->setAction($options['action']);

        $this->addEvents($builder);

    }

    /**
     * {@inheritdoc}
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(
            [
                'data_class'         => CustomObject::class,
                'allow_extra_fields' => true,
                'csrf_protection'    => false,
            ]
        );
    }

    /**
     * @param FormBuilderInterface $builder
     */
    private function addEvents(FormBuilderInterface $builder): void
    {
        $builder->addEventListener(FormEvents::POST_SET_DATA, function (FormEvent $event) {
            /** @var CustomObject $customObject */
            $customObject = $event->getData();
            $customFields = $customObject->getCustomFields();

            if (!$customFields) {
                return;
            }

            /** @var CustomField $customField */
            foreach($customFields as $customField) {
                if (!$customField->getTypeObject()) {
                    // Should not happen. Every CF MUST HAVE type object.
                    $customField->setTypeObject($this->customFieldTypeProvider->getType($customField->getType()));
                }
            }
        });
    }
}
