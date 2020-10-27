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
use MauticPlugin\CustomObjectsBundle\CustomFieldType\IntType;
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

final class CampaignConditionFieldValueTypeTest extends TestCase
{
    /**
     * @var MockObject|CustomFieldModel
     */
    private $customFieldModelMock;

    /**
     * @var MockObject|CustomItemRouteProvider
     */
    private $customItemRouterMock;

    /**
     * @var MockObject|TranslatorInterface
     */
    private $translatorMock;

    /**
     * @var CampaignConditionFieldValueType
     */
    private $campaignConditionFieldValueType;

    protected function setUp()
    {
        parent::setUp();

        $this->customFieldModelMock            = $this->createMock(CustomFieldModel::class);
        $this->customItemRouterMock            = $this->createMock(CustomItemRouteProvider::class);
        $this->translatorMock                  = $this->createMock(TranslatorInterface::class);
        $this->campaignConditionFieldValueType = new CampaignConditionFieldValueType(
            $this->customFieldModelMock,
            $this->customItemRouterMock,
            $this->translatorMock
        );
    }

    public function testBuildForm()
    {
        $customObject = new CustomObject();
        $customObject->setId(42);
        $customField = new CustomField();
        $customField->setId(42);
        $customField->setLabel('Cheese');
        $customField->setType('int');
        $filterOperatorProviderInterfaceMock = $this->createMock(FilterOperatorProviderInterface::class);
        $filterOperatorProviderInterfaceMock
            ->expects(self::any())
            ->method('getAllOperators')
            ->willReturn([
                '=' => ['label' => 'a'],
                '!=' => ['label' => 'b'],
            ]);
        $this->translatorMock
            ->expects(self::at(0))
            ->method('trans')
            ->with('a')
            ->willReturn('a');
        $this->translatorMock
            ->expects(self::at(1))
            ->method('trans')
            ->with('b')
            ->willReturn('b');
        $this->translatorMock
            ->expects(self::at(2))
            ->method('trans')
            ->with('a')
            ->willReturn('a');
        $this->translatorMock
            ->expects(self::at(3))
            ->method('trans')
            ->with('b')
            ->willReturn('b');

        $customField->setTypeObject(new IntType($this->translatorMock, $filterOperatorProviderInterfaceMock));
        $customFields = [42 => $customField];
        $this->customFieldModelMock
            ->expects(self::once())
            ->method('fetchCustomFieldsForObject')
            ->with($customObject)
            ->willReturn($customFields);
        $formBuilderMock = $this->createMock(FormBuilderInterface::class);
        $formBuilderMock
            ->expects(self::at(0))
            ->method('add')
            ->with(
                'field',
                ChoiceType::class,
                [
                    'required' => true,
                    'label'    => 'custom.item.field',
                    'choices'  => [
                        'Cheese' => 42
                    ],
                    'attr'     => [
                        'class' => 'form-control',
                    ],
                    'choice_attr' =>  [
                        42 => [
                            'data-operators'  => '{"=":"a","!=":"b"}',
                            'data-options'    => '[]',
                            'data-field-type' => 'int',
                        ]
                    ]
                ]
            );
        $options = [
            'customObject' => $customObject,
            'data' => [
                'field' => 42
            ]
        ];
        $formBuilderMock
            ->expects(self::at(1))
            ->method('add')
            ->with(
                'operator',
                ChoiceType::class,
                [
                    'required' => true,
                    'label'    => 'custom.item.operator',
                    'choices'  => [
                        'a' => '=',
                        'b' => '!=',
                    ],
                    'attr'     => ['class' => 'link-custom-item-id'],
                ]
            );
        $formConfigBuilderMock = $this->createMock(FormConfigBuilderInterface::class);
        $formBuilderMock
            ->expects(self::at(2))
            ->method('get')
            ->with('operator')
            ->willReturn($formConfigBuilderMock);

        $formBuilderMock
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

        $formBuilderMock
            ->expects(self::at(4))
            ->method('add')
            ->with(
                'customObjectId',
                HiddenType::class,
                ['data' => 42]
            );

        $this->campaignConditionFieldValueType->buildForm($formBuilderMock, $options);
    }

    public function testConfigureOptions()
    {
        $resolver = $this->createMock(OptionsResolver::class);
        $resolver
            ->expects(self::once())
            ->method('setRequired')
            ->with(['customObject']);

        $this->campaignConditionFieldValueType->configureOptions($resolver);
    }
}
