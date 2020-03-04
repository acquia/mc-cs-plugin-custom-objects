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

namespace MauticPlugin\CustomObjectsBundle\EventListener;

use Mautic\LeadBundle\LeadEvents;
use Mautic\LeadBundle\Event\LeadListFiltersOperatorsEvent;
use Mautic\LeadBundle\Event\TypeOperatorsEvent;
use MauticPlugin\CustomObjectsBundle\Model\CustomObjectModel;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Mautic\LeadBundle\Event\FilterPropertiesTypeEvent;

class FilterOperatorSubscriber implements EventSubscriberInterface
{
    public const WITHIN_VALUES = 'withinValues';
    /**
     * @var CustomObjectModel
     */
    private $customObjectModel;

    /**
     * @param CustomObjectModel $customObjectModel
     */
    public function __construct(CustomObjectModel $customObjectModel)
    {
        $this->customObjectModel = $customObjectModel;
    }

    /**
     * @return mixed[]
     */
    public static function getSubscribedEvents(): array
    {
        return [
            LeadEvents::LIST_FILTERS_OPERATORS_ON_GENERATE => ['onOperatorsGenerate', 0],
            LeadEvents::COLLECT_OPERATORS_FOR_FIELD_TYPE => ['addWithinFieldValuesOperator', -9999],
            LeadEvents::ADJUST_FILTER_FORM_TYPE_FOR_FIELD => ['onSegmentFilterFormHandleWithinFieldFormType', 2000],
        ];
    }

    /**
     * @param LeadListFiltersOperatorsEvent $event
     */
    public function onOperatorsGenerate(LeadListFiltersOperatorsEvent $event)
    {
        $event->addOperator(self::WITHIN_VALUES, [
            'label'       => 'custom.within.field.values.label',
            'expr'        => self::WITHIN_VALUES,
            'negate_expr' => 'notWithinValues',
        ]);
    }

    /**
     * @param TypeOperatorsEvent $event
     */
    public function addWithinFieldValuesOperator(TypeOperatorsEvent $event)
    {
        $typeOperators = $event->getOperatorsForAllFieldTypes();

        foreach ($typeOperators as $fieldType => $typeOperator) {
            if (empty($typeOperator['include'])) {
                continue;
            }
            $typeOperator['include'][] = self::WITHIN_VALUES;
            $event->setOperatorsForFieldType($fieldType, $typeOperator);
        }
    }

    public function onSegmentFilterFormHandleWithinFieldFormType(FilterPropertiesTypeEvent $event): void
    {
        if (!$event->operatorIsOneOf(self::WITHIN_VALUES)) {
            return;
        }

        $form          = $event->getFilterPropertiesForm();
        $customObjects = $this->customObjectModel->fetchAllPublishedEntities();
        $choices  = [];

        foreach ($customObjects as $customObject) {
            $choices[$customObject->getNameSingular()] = ['Name' => "object:{$customObject->getId()}:name"];
            foreach ($customObject->getCustomFields() as $field) {
                $choices[$customObject->getNameSingular()][$field->getName()] = $field->getId();
            }
        }

        $form->add(
            'filter',
            ChoiceType::class,
            [
                'label'   => false,
                'data'    => $form->getData()['filter'] ?? '',
                'choices' => $choices,
                'attr'    => ['class' => 'form-control'],
            ]
        );

        $event->stopPropagation();
    }
}
