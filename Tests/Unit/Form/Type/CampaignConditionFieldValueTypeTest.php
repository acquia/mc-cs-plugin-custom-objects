<?php

declare(strict_types=1);

/*
 * @copyright   2020 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc
 *
 * @link        https://mautic.com
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\CustomObjectsBundle\Tests\Unit\Form\Type;

use MauticPlugin\CustomObjectsBundle\CustomFieldType\CustomFieldTypeInterface;
use MauticPlugin\CustomObjectsBundle\Entity\CustomField;
use MauticPlugin\CustomObjectsBundle\Entity\CustomObject;
use MauticPlugin\CustomObjectsBundle\Form\Type\CampaignConditionFieldValueType;
use MauticPlugin\CustomObjectsBundle\Model\CustomFieldModel;
use MauticPlugin\CustomObjectsBundle\Provider\CustomItemRouteProvider;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Translation\TranslatorInterface;

final class CampaignConditionFieldValueTypeTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var MockObject|FormBuilderInterface
     */
    private $formBuilder;

    /**
     * @var MockObject|CustomFieldModel
     */
    private $customFieldModel;

    /**
     * @var MockObject|CustomItemRouteProvider
     */
    private $routeProvider;

    /**
     * @var MockObject|TranslatorInterface
     */
    private $translator;

    /**
     * @var CampaignConditionFieldValueType
     */
    private $formType;

    protected function setUp(): void
    {
        parent::setUp();

        $this->formBuilder      = $this->createMock(FormBuilderInterface::class);
        $this->customFieldModel = $this->createMock(CustomFieldModel::class);
        $this->routeProvider    = $this->createMock(CustomItemRouteProvider::class);
        $this->translator       = $this->createMock(TranslatorInterface::class);
        $this->formType         = new CampaignConditionFieldValueType(
            $this->customFieldModel,
            $this->routeProvider,
            $this->translator
        );
    }

    public function testBuildFormFor(): void
    {
        $customObject    = new CustomObject();
        $customField     = $this->createMock(CustomField::class);
        $customFieldType = $this->createMock(CustomFieldTypeInterface::class);
        $options         = [
            'customObject' => $customObject,
            'data' => [
                'field' => 24,
            ],
        ];

        $customFieldType->method('getOperatorOptions')->willReturn(['equals' => '=']);

        $customField->method('getName')->willReturn('Field G');
        $customField->method('getId')->willReturn(45);
        $customField->method('getType')->willReturn('some_type');
        $customField->method('getChoices')->willReturn([12 => 'Choice 12']);
        $customField->method('getTypeObject')->willReturn($customFieldType);

        $this->customFieldModel->expects($this->once())
            ->method('fetchCustomFieldsForObject')
            ->with($customObject)
            ->willReturn([24 => $customField]);

        $this->formBuilder->expects($this->once())
            ->method('get')
            ->with('operator')
            ->willReturnSelf();

        $this->formBuilder->expects($this->at(0))
            ->method('add')
            ->with(
                'field',
                ChoiceType::class,
                $this->callback(
                    function(array $options) {
                        $this->assertSame(['Field G' => 45], $options['choices']);
                        $this->assertSame(
                            [
                                'data-operators'  => '{"equals":"="}',
                                'data-options'    => '{"Choice 12":12}',
                                'data-field-type' => 'some_type',
                            ],
                            $options['choice_attr'](24)
                        );

                        return true;
                    }
                )
            );

        $this->formType->buildForm($this->formBuilder, $options);
    }
}
