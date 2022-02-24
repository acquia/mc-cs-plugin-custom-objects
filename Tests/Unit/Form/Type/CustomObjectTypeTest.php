<?php

declare(strict_types=1);

namespace MauticPlugin\CustomObjectsBundle\Tests\Unit\Form\Type;

use Doctrine\ORM\EntityManager;
use Mautic\CategoryBundle\Form\Type\CategoryListType;
use Mautic\CoreBundle\Form\Type\FormButtonsType;
use Mautic\CoreBundle\Form\Type\YesNoButtonGroupType;
use MauticPlugin\CustomObjectsBundle\Entity\CustomObject;
use MauticPlugin\CustomObjectsBundle\Form\Type\CustomFieldType;
use MauticPlugin\CustomObjectsBundle\Form\Type\CustomObjectType;
use MauticPlugin\CustomObjectsBundle\Provider\CustomFieldTypeProvider;
use MauticPlugin\CustomObjectsBundle\Repository\CustomObjectRepository;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CustomObjectTypeTest extends TestCase
{
    /**
     * @var MockObject|EntityManager
     */
    private $entityManager;

    /**
     * @var MockObject|CustomFieldTypeProvider
     */
    private $customFieldTypeProvider;

    /**
     * @var MockObject|CustomObjectRepository
     */
    private $customObjectRepository;

    /**
     * @var CustomObjectType
     */
    private $type;

    protected function setUp(): void
    {
        $this->entityManager           = $this->createMock(EntityManager::class);
        $this->customFieldTypeProvider = $this->createMock(CustomFieldTypeProvider::class);
        $this->customObjectRepository  = $this->createMock(CustomObjectRepository::class);

        $this->type = new CustomObjectType(
            $this->entityManager,
            $this->customFieldTypeProvider,
            $this->customObjectRepository
        );
    }

    public function testBuildFormNewObject()
    {
        $this->buildForm(true, 'nameSingular', 'alias', 'action');
    }

    public function testBuildFormExistingObject()
    {
        $this->buildForm(false, 'nameSingular1', 'alias1', 'action1');
    }

    public function testConfigureOptions()
    {
        $options = [
            'data_class'         => CustomObject::class,
            'allow_extra_fields' => true,
            'csrf_protection'    => false,
        ];

        /** @var MockObject|OptionsResolver $resolver */
        $resolver = $this->createMock(OptionsResolver::class);
        $resolver->expects($this->once())
            ->method('setDefaults')
            ->with($options);

        $this->type->configureOptions($resolver);
    }

    private function buildForm(bool $isNewObject, string $coNameSingular, string $coAlias, string $action)
    {
        $customObject = $this->createMock(CustomObject::class);
        $customObject->method('isNew')
            ->willReturn($isNewObject);
        $customObject->method('getNameSingular')
            ->willReturn($coNameSingular);
        $customObject->method('getAlias')
            ->willReturn($coAlias);

        $options = [
            'data'   => $customObject,
            'action' => $action,
        ];

        $this->customObjectRepository->expects($this->once())
            ->method('getMasterObjectChoices')
            ->with($customObject)
            ->willReturn(['Custom Object A' => 123]);

        /** @var MockObject|FormBuilderInterface $builder */
        $builder = $this->createMock(FormBuilderInterface::class);
        $builder
            ->method('add')
            ->withConsecutive(
                [
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
                    ],
                ],
                [
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
                    ],
                ],
                [
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
                    ],
                ],
                [
                    'description',
                    TextareaType::class,
                    [
                        'label'      => 'custom.object.description.label',
                        'required'   => false,
                        'label_attr' => ['class' => 'control-label'],
                        'attr'       => [
                            'class' => 'form-control editor',
                        ],
                    ],
                ],
                [
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
                    ],
                ],
                [
                    'masterObject',
                    ChoiceType::class,
                    [
                        'label'         => 'custom.object.relationship.master_object.label',
                        'choices'       => ['Custom Object A' => 123],
                        'required'      => true,
                        'placeholder'   => '',
                        'label_attr'    => ['class' => 'control-label'],
                        'attr'          => [
                            'class'        => 'form-control',
                            'data-show-on' => '{"custom_object_type":["'.CustomObject::TYPE_RELATIONSHIP.'"]}',
                        ],
                        'disabled'      => !$isNewObject,
                    ],
                ],
                [
                    'category',
                    CategoryListType::class, ['bundle' => 'global'],
                ],
                [
                    'isPublished',
                    YesNoButtonGroupType::class,
                ],
                [
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
                    ],
                ],
                [
                    'buttons',
                    FormButtonsType::class,
                    [
                        'cancel_onclick' => "mQuery('form[name=custom_object]').attr('action', mQuery('form[name=custom_object]').attr('action').replace('/save', '/cancel'));",
                    ],
                ]
            );

        $builder->expects($this->once())
            ->method('get')
            ->with('masterObject')
            ->willReturn($builder);
        $builder->expects($this->once())
            ->method('addModelTransformer');
        $builder->expects($this->once())
            ->method('setAction')
            ->with($action);

        $this->type->buildForm($builder, $options);
    }
}
