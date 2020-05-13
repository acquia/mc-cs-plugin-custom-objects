<?php

declare(strict_types=1);

/*
 * @copyright   2020 Mautic, Inc. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.com
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\CustomObjectsBundle\Tests\Unit\Form\Type;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\QueryBuilder;
use Mautic\CategoryBundle\Form\Type\CategoryListType;
use Mautic\CoreBundle\Form\Type\FormButtonsType;
use Mautic\CoreBundle\Form\Type\YesNoButtonGroupType;
use MauticPlugin\CustomObjectsBundle\Entity\CustomObject;
use MauticPlugin\CustomObjectsBundle\Form\Type\CustomFieldType;
use MauticPlugin\CustomObjectsBundle\Form\Type\CustomObjectType;
use MauticPlugin\CustomObjectsBundle\Provider\CustomFieldTypeProvider;
use MauticPlugin\CustomObjectsBundle\Repository\CustomObjectRepository;
use PHPUnit\Framework\TestCase;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CustomObjectTypeTest extends TestCase
{
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject
     */
    private $entityManager;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject
     */
    private $customFieldTypeProvider;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject
     */
    private $customObjectRepository;

    /**
     * @var CustomObjectType
     */
    private $type;

    public function setUp()
    {
        $this->entityManager = $this->createMock(EntityManager::class);
        $this->customFieldTypeProvider = $this->createMock(CustomFieldTypeProvider::class);
        $this->customObjectRepository = $this->createMock(CustomObjectRepository::class);

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

        $resolver = $this->createMock(OptionsResolver::class);
        $resolver->expects($this->once())
            ->method('setDefaults')
            ->with($options);

        $this->type->configureOptions($resolver);
    }

    private function buildForm(bool $isNewObject, string $coNameSingular, string $coAlias, string $action)
    {
        $customObject = new CustomObject();
        $customObject->setNew($isNewObject);
        $customObject->setNameSingular($coNameSingular);
        $customObject->setAlias($coAlias);

        $options = [
            'data'   => $customObject,
            'action' => $action,
        ];

        $queryBuilder = $this->createMock(QueryBuilder::class);

        $this->customObjectRepository->expects($this->once())
            ->method('getMasterObjectQueryBuilder')
            ->with($customObject)
            ->willReturn($queryBuilder);

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
                    ]
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
                    ]
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
                    ]
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
                    ]
                ],
                [
                    'relationship',
                    ChoiceType::class,
                    [
                        'label'      => 'custom.object.relationship.label',
                        'required'   => true,
                        'placeholder' => '',
                        'empty_data' => null,
                        'label_attr' => ['class' => 'control-label'],
                        'attr'       => [
                            'class'        => 'form-control',
                            'data-show-on' => '{"custom_object_type":["'.CustomObject::TYPE_RELATIONSHIP.'"]}',
                        ],
                        'choices'    => [
                            'custom.object.relationship.many_to_many' => CustomObject::RELATIONSHIP_MANY_TO_MANY,
                            'custom.object.relationship.one_to_one'   => CustomObject::RELATIONSHIP_ONE_TO_ONE,
                        ],
                        'disabled'   => !$isNewObject,
                    ]
                ],
                [
                    'masterObject',
                    EntityType::class,
                    [
                        'label'         => 'custom.object.relationship.master_object.label',
                        'class'         => CustomObject::class,
                        'required'      => true,
                        'placeholder'   => '',
                        'empty_data'    => null,
                        'label_attr'    => ['class' => 'control-label'],
                        'attr'          => [
                            'class'        => 'form-control',
                            'data-show-on' => '{"custom_object_type":["'.CustomObject::TYPE_RELATIONSHIP.'"]}',
                        ],
                        'choice_label'  => function ($customObject) {
                            return $customObject->getNameSingular()." (".$customObject->getAlias().")";
                        },
                        'choice_value'  => 'id',
                        'query_builder' => $queryBuilder,
                        'disabled'      => !$isNewObject,
                    ]
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
                    ]
                ],
                [
                    'buttons',
                    FormButtonsType::class,
                    [
                        'cancel_onclick' => "mQuery('form[name=custom_object]').attr('method', 'get').attr('action', mQuery('form[name=custom_object]').attr('action').replace('/save', '/cancel'));",
                    ]
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
