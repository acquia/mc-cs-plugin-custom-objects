<?php

declare(strict_types=1);

/*
 * @copyright   2019 Mautic, Inc. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.com
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\CustomObjectsBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use MauticPlugin\CustomObjectsBundle\Provider\CustomItemRouteProvider;
use Symfony\Component\Translation\TranslatorInterface;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use MauticPlugin\CustomObjectsBundle\Model\CustomFieldModel;
use MauticPlugin\CustomObjectsBundle\Entity\CustomFieldOption;

class CampaignConditionFieldValueType extends AbstractType
{
    /**
     * @var CustomFieldModel
     */
    protected $customFieldModel;

    /**
     * @var CustomItemRouteProvider
     */
    protected $routeProvider;

    /**
     * @var TranslatorInterface
     */
    protected $translator;

    /**
     * @param CustomFieldModel        $customFieldModel
     * @param CustomItemRouteProvider $routeProvider
     * @param TranslatorInterface     $translator
     */
    public function __construct(
        CustomFieldModel $customFieldModel,
        CustomItemRouteProvider $routeProvider,
        TranslatorInterface $translator
    ) {
        $this->customFieldModel = $customFieldModel;
        $this->routeProvider    = $routeProvider;
        $this->translator       = $translator;
    }

    /**
     * @param FormBuilderInterface $builder
     * @param mixed[]              $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $fields = $this->customFieldModel->fetchCustomFieldsForObject($options['customObject']);

        $builder->add(
            'field',
            ChoiceType::class,
            [
                'required' => true,
                'label'    => 'custom.item.field',
                'choices'  => $fields,
                'attr'     => [
                    'class' => 'form-control',
                ],
                'choice_attr' => function ($fieldId) use ($fields) {
                    $field     = $fields[$fieldId];
                    $operators = $field->getTypeObject()->getOperatorOptions($this->translator);
                    $options   = $field->getOptions()->map(function (CustomFieldOption $option) {
                        return ['value' => $option->getValue(), 'label' => $option->getLabel()];
                    });

                    return [
                        'data-operators'  => json_encode($operators),
                        'data-options'    => json_encode($options->toArray()),
                        'data-field-type' => $field->getType(),
                    ];
                },
            ]
        );

        if (isset($options['data']['field'])) {
            $selectedField = $fields[$options['data']['field']];
        } else {
            $selectedField = array_values($fields)[0];
        }

        $operators = $selectedField->getTypeObject()->getOperatorOptions($this->translator);

        $builder->add(
            'operator',
            ChoiceType::class,
            [
                'required' => true,
                'label'    => 'custom.item.operator',
                'choices'  => $operators,
                'attr'     => ['class' => 'link-custom-item-id'],
            ]
        );

        // Disable operator choice validation as each field has different operators.
        $builder->get('operator')->resetViewTransformers();

        $builder->add(
            'value',
            TextType::class,
            [
                'required' => true,
                'label'    => 'custom.item.field.value',
                'attr'     => ['class' => 'form-control'],
            ]
        );

        $builder->add(
            'customObjectId',
            HiddenType::class,
            ['data' => $options['customObject']->getId()]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver): void
    {
        $resolver->setRequired(['customObject']);
    }
}
