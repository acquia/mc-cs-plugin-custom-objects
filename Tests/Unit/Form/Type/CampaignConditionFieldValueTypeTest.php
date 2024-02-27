<?php

declare(strict_types=1);

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
use Symfony\Contracts\Translation\TranslatorInterface;

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

    protected function setUp(): void
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

    public function testBuildForm(): void
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
                '='  => ['label' => 'a'],
                '!=' => ['label' => 'b'],
            ]);
        $this->translatorMock
            ->method('trans')
            ->withConsecutive(['a'], ['b'], ['a'], ['b'])
            ->willReturnOnConsecutiveCalls('a', 'b', 'a', 'b');

        $customField->setTypeObject(new IntType($this->translatorMock, $filterOperatorProviderInterfaceMock));
        $customFields = [42 => $customField];
        $this->customFieldModelMock
            ->expects(self::once())
            ->method('fetchCustomFieldsForObject')
            ->with($customObject)
            ->willReturn($customFields);
        $formBuilderMock = $this->createMock(FormBuilderInterface::class);
        $formBuilderMock
            ->method('add')
            ->withConsecutive(
                [
                    'field',
                    ChoiceType::class,
                    [
                        'required' => true,
                        'label'    => 'custom.item.field',
                        'choices'  => [
                            'Cheese' => 42,
                        ],
                        'attr'     => [
                            'class' => 'form-control',
                        ],
                        'choice_attr' => [
                            42 => [
                                'data-operators'  => '{"=":"a","!=":"b"}',
                                'data-options'    => '[]',
                                'data-field-type' => 'int',
                            ],
                        ],
                    ],
                ],
                [
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
                    ],
                ],
                [
                    'value',
                    TextType::class,
                    [
                        'required' => true,
                        'label'    => 'custom.item.field.value',
                        'attr'     => ['class' => 'form-control'],
                    ],
                ],
                [
                    'customObjectId',
                    HiddenType::class,
                    ['data' => 42],
                ]
            );
        $options = [
            'customObject' => $customObject,
            'data'         => [
                'field' => 42,
            ],
        ];
        $formConfigBuilderMock = $this->createMock(FormConfigBuilderInterface::class);
        $formBuilderMock
            ->expects($this->once())
            ->method('get')
            ->with('operator')
            ->willReturn($formConfigBuilderMock);

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
