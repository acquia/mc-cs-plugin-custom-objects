<?php

declare(strict_types=1);

/*
 * @copyright   2020 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\CustomObjectsBundle\Tests\Unit\Form\Type;

use Mautic\LeadBundle\Provider\FilterOperatorProviderInterface;
use MauticPlugin\CustomObjectsBundle\CustomFieldType\DateType;
use MauticPlugin\CustomObjectsBundle\Entity\CustomField;
use MauticPlugin\CustomObjectsBundle\Entity\CustomObject;
use MauticPlugin\CustomObjectsBundle\Form\Type\CampaignConditionFieldValueType;
use MauticPlugin\CustomObjectsBundle\Model\CustomFieldModel;
use MauticPlugin\CustomObjectsBundle\Provider\CustomItemRouteProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormConfigBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Translation\TranslatorInterface;

class CampaignConditionFieldValueTypeTest extends TestCase
{
    /**
     * @var CustomFieldModel|MockObject
     */
    private $customFieldModel;

    /**
     * @var CustomItemRouteProvider|MockObject
     */
    private $routeProvider;

    /**
     * @var MockObject|TranslatorInterface
     */
    private $translator;

    /**
     * @var CampaignConditionFieldValueType
     */
    private $form;

    protected function setUp()
    {
        parent::setUp();

        $this->customFieldModel = $this->createMock(CustomFieldModel::class);
        $this->routeProvider = $this->createMock(CustomItemRouteProvider::class);
        $this->translator = $this->createMock(TranslatorInterface::class);

        $this->form = new CampaignConditionFieldValueType(
            $this->customFieldModel,
            $this->routeProvider,
            $this->translator
        );
    }

    public function testBuildForm()
    {
        $filterOperatorProvider = $this->createMock(FilterOperatorProviderInterface::class);
        $fieldType = new DateType($this->translator, $filterOperatorProvider);
        $field = new CustomField();
        $field->setTypeObject($fieldType);
        $fields = [$field];

        $customObject = new CustomObject();
        $customObject->addCustomField($field);

        $options['customObject'] = $customObject;
        $options['data']['field'] = 0;

        $operators = [];

        $this->customFieldModel->expects(self::once())
            ->method('fetchCustomFieldsForObject')
            ->with($options['customObject'])
            ->willReturn($fields);

        $builder = $this->createMock(FormBuilderInterface::class);
        $builder
            ->expects(self::at(0))
            ->method('add')
            ->with(
                'field',
                ChoiceType::class,
                [
                        'required' => true,
                        'label'    => 'custom.item.field',
                        'choices'  => $fields,
                        'attr'     => [
                            'class' => 'form-control',
                        ],
                        'choice_attr' => array_map(
                            function ($field) {
                                return [
                                    'data-operators'  => json_encode($field->getTypeObject()->getOperatorOptions()),
                                    'data-options'    => json_encode($field->getChoices()),
                                    'data-field-type' => $field->getType(),
                                ];
                            },
                            $fields
                        )
                    ]
            );

        $builder
            ->expects(self::at(1))
            ->method('add')
            ->with(
                'operator',
                ChoiceType::class,
                [
                    'required' => true,
                    'label'    => 'custom.item.operator',
                    'choices'  => $operators,
                    'attr'     => ['class' => 'link-custom-item-id'],
                ]
            );

        $formConfigBuilder = $this->createMock(FormConfigBuilderInterface::class);
        $formConfigBuilder
            ->expects(self::once())
            ->method('resetViewTransformers');

        $builder
            ->expects(self::at(2))
            ->method('get')
            ->with('operator')
            ->willReturn($formConfigBuilder);

        $builder
            ->expects(self::at(3))
            ->method('add')
            ->with(
                'value',
                TextType::class,
                [
                    'required' => true,
                    'label'    => 'custom.item.field.value',
                    'attr'     => ['class' => 'form-control'],
                ]
            );

        $builder
            ->expects(self::at(4))
            ->method('add')
            ->with(
                'customObjectId',
                HiddenType::class,
                ['data' => $options['customObject']->getId()]
            );

        $this->form->buildForm($builder, $options);
    }

    public function testConfigureOptions()
    {
        $resolver = $this->createMock(OptionsResolver::class);
        $resolver->expects(self::once())
            ->method('setRequired')
            ->with(['customObject']);

        $this->form->configureOptions($resolver);
    }
}
