<?php

declare(strict_types=1);

namespace MauticPlugin\CustomObjectsBundle\Form\Type;

use Doctrine\ORM\EntityManager;
use Mautic\CategoryBundle\Form\Type\CategoryListType;
use Mautic\CoreBundle\Form\DataTransformer\IdToEntityModelTransformer;
use Mautic\CoreBundle\Form\EventListener\CleanFormSubscriber;
use Mautic\CoreBundle\Form\Type\FormButtonsType;
use Mautic\CoreBundle\Form\Type\YesNoButtonGroupType;
use MauticPlugin\CustomObjectsBundle\Entity\CustomField;
use MauticPlugin\CustomObjectsBundle\Entity\CustomObject;
use MauticPlugin\CustomObjectsBundle\Provider\CustomFieldTypeProvider;
use MauticPlugin\CustomObjectsBundle\Repository\CustomObjectRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CustomObjectType extends AbstractType
{
    /**
     * @var EntityManager
     */
    private $entityManager;

    /**
     * @var CustomFieldTypeProvider
     */
    private $customFieldTypeProvider;

    /**
     * @var CustomObjectRepository
     */
    private $customObjectRepository;

    public function __construct(EntityManager $entityManager, CustomFieldTypeProvider $customFieldTypeProvider, CustomObjectRepository $customObjectRepository)
    {
        $this->entityManager           = $entityManager;
        $this->customFieldTypeProvider = $customFieldTypeProvider;
        $this->customObjectRepository  = $customObjectRepository;
    }

    /**
     * @param mixed[] $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->addEventSubscriber(new CleanFormSubscriber(['description' => 'html']));

        $customObject = $options['data'];
        $isNewObject  = $customObject->isNew();

        $builder->add(
            'alias',
            TextType::class,
            [
                'label'      => 'custom.object.alias.label',
                'required'   => false,
                'label_attr' => ['class' => 'control-label'],
                'attr'       => [
                    'class'    => 'form-control',
                    'tooltip'  => 'custom.field.help.alias',
                    'readOnly' => !$isNewObject,
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
                    'class'   => 'form-control',
                    'tooltip' => 'custom.object.help.name.singular',
                ],
            ]
        );

        $builder->add(
            'namePlural',
            TextType::class,
            [
                'label'      => 'custom.object.name.plural.label',
                'required'   => true,
                'label_attr' => ['class' => 'control-label'],
                'attr'       => [
                    'class'   => 'form-control',
                    'tooltip' => 'custom.object.help.name.plural',
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
                    'class' => 'form-control editor',
                ],
            ]
        );

        $builder->add(
            'type',
            ChoiceType::class,
            [
                'label'      => 'custom.object.type.label',
                'required'   => true,
                'label_attr' => ['class' => 'control-label'],
                'attr'       => [
                    'class' => 'form-control',
                ],
                'disabled'   => !$isNewObject,
                'choices'    => [
                    'custom.object.type.master'       => CustomObject::TYPE_MASTER,
                    'custom.object.type.relationship' => CustomObject::TYPE_RELATIONSHIP,
                ],
            ]
        );

        $transformer = new IdToEntityModelTransformer($this->entityManager, CustomObject::class);

        $builder->add(
            'masterObject',
            ChoiceType::class,
            [
                'label'         => 'custom.object.relationship.master_object.label',
                'choices'       => $this->customObjectRepository->getMasterObjectChoices($customObject),
                'required'      => true,
                'placeholder'   => '',
                'label_attr'    => ['class' => 'control-label'],
                'attr'          => [
                    'class'        => 'form-control',
                    'data-show-on' => '{"custom_object_type":["'.CustomObject::TYPE_RELATIONSHIP.'"]}',
                ],
                'disabled'      => !$isNewObject,
            ]
        );
        $builder->get('masterObject')->addModelTransformer($transformer);

        $builder->add('category', CategoryListType::class, ['bundle' => 'global']);
        $builder->add('isPublished', YesNoButtonGroupType::class);

        $builder->add(
            'customFields',
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
                'cancel_onclick' => "mQuery('form[name=custom_object]').attr('action', mQuery('form[name=custom_object]').attr('action').replace('/save', '/cancel'));",
            ]
        );

        $builder->setAction($options['action']);

        $this->addEvents($builder);
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(
            [
                'data_class'         => CustomObject::class,
                'allow_extra_fields' => true,
                'csrf_protection'    => false,
            ]
        );
    }

    private function addEvents(FormBuilderInterface $builder): void
    {
        $builder->addEventListener(
            FormEvents::POST_SET_DATA,
            function (FormEvent $event): void {
                /** @var CustomObject $customObject */
                $customObject = $event->getData();
                $customFields = $customObject->getCustomFields();

                if (empty($customFields)) {
                    return;
                }

                /** @var CustomField $customField */
                foreach ($customFields as $customField) {
                    if (!$customField->getTypeObject()) {
                        // Should not happen. Every CF MUST HAVE type object.
                        $customField->setTypeObject($this->customFieldTypeProvider->getType($customField->getType()));
                    }
                }
            }
        );
    }
}
