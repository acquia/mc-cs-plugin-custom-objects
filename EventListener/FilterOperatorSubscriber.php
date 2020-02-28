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
use Mautic\LeadBundle\Event\LeadListFiltersChoicesEvent;
use Mautic\LeadBundle\Event\TypeOperatorsEvent;
use Mautic\LeadBundle\Event\ListFieldChoicesEvent;
use MauticPlugin\CustomObjectsBundle\Model\CustomObjectModel;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

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
            LeadEvents::COLLECT_FILTER_CHOICES_FOR_LIST_FIELD_TYPE => ['addWithinFieldValuesChoices', -9999],
            // LeadEvents::LIST_FILTERS_CHOICES_ON_GENERATE => ['onGenerateSegmentFilters', -9999],
        ];
    }

    /**
     * @param LeadListFiltersOperatorsEvent $event
     */
    public function onOperatorsGenerate(LeadListFiltersOperatorsEvent $event)
    {
        $event->addOperator(self::WITHIN_VALUES, [
            'label'       => 'custom.within.field.values.label',
            'choices'     => ['a' => ['b' => 'k']],
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
            $typeOperator['include'][] = self::WITHIN_VALUES;
            $event->setOperatorsForFieldType($fieldType, $typeOperator);
        }
    }

    /**
     * @param ListFieldChoicesEvent $event
     */
    public function addWithinFieldValuesChoices(ListFieldChoicesEvent $event)
    {
        $customObjects = $this->customObjectModel->fetchAllPublishedEntities();
        $choices = [];

        $choices['a']['b'] = 'c';

        // foreach ($customObjects as $customObject) {
        //     $choices[]
        // }
        $event->setChoicesForFieldType(self::WITHIN_VALUES, $choices);
    }

    /**
     * @param LeadListFiltersChoicesEvent $event
     */
    // public function onGenerateSegmentFilters(LeadListFiltersChoicesEvent $event)
    // {
    //     $choiceObjects = $event->getChoices();

    //     foreach ($choiceObjects as $object => $choices) {
    //         foreach ($choices as $choiceKey => $choiceConfig) {
    //             $choiceConfig['operators']['withinFieldValue'] = 'withinFieldValue';
    //             $event->setChoice($object, $choiceKey, $choiceConfig);
    //         }
    //     }
    // }
}
