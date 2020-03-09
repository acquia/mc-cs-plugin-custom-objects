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

use Mautic\LeadBundle\Event\FieldOperatorsEvent;
use Mautic\LeadBundle\Event\FilterPropertiesTypeEvent;
use Mautic\LeadBundle\Event\LeadListFiltersOperatorsEvent;
use Mautic\LeadBundle\Event\SegmentOperatorQueryBuilderEvent;
use Mautic\LeadBundle\LeadEvents;
use MauticPlugin\CustomObjectsBundle\Exception\NotFoundException;
use MauticPlugin\CustomObjectsBundle\Model\CustomObjectModel;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;

class FilterOperatorSubscriber implements EventSubscriberInterface
{
    public const WITHIN_VALUES = 'withinValues';

    /**
     * @var CustomObjectModel
     */
    private $customObjectModel;

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
            LeadEvents::LIST_FILTERS_OPERATORS_ON_GENERATE             => ['onOperatorsGenerate', 0],
            LeadEvents::COLLECT_OPERATORS_FOR_FIELD                    => ['addWithinFieldValuesOperator', -9999],
            LeadEvents::ADJUST_FILTER_FORM_TYPE_FOR_FIELD              => ['onSegmentFilterFormHandleWithinFieldFormType', 2000],
            LeadEvents::LIST_FILTERS_OPERATOR_QUERYBUILDER_ON_GENERATE => ['onWithinFieldValuesBuilder', 0],
        ];
    }

    public function onOperatorsGenerate(LeadListFiltersOperatorsEvent $event)
    {
        $event->addOperator(self::WITHIN_VALUES, [
            'label'       => 'custom.within.field.values.label',
            'expr'        => self::WITHIN_VALUES,
            'negate_expr' => 'notWithinValues',
        ]);
    }

    public function addWithinFieldValuesOperator(FieldOperatorsEvent $event)
    {
        if ('generated_email_domain' === $event->getField()) {
            $event->addOperator(self::WITHIN_VALUES);
        }
    }

    public function onSegmentFilterFormHandleWithinFieldFormType(FilterPropertiesTypeEvent $event): void
    {
        if (!$event->operatorIsOneOf(self::WITHIN_VALUES)) {
            return;
        }

        $form          = $event->getFilterPropertiesForm();
        $customObjects = $this->customObjectModel->fetchAllPublishedEntities();
        $choices       = [];

        foreach ($customObjects as $customObject) {
            $choices[$customObject->getNameSingular()] = ['Name' => "custom-object:{$customObject->getId()}:name"];
            // As we have to cut scope to deliver in time, let's not support all custom fields just yet.
            // foreach ($customObject->getCustomFields() as $field) {
            //     $choices[$customObject->getNameSingular()][$field->getName()] = $field->getId();
            // }
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

    public function onWithinFieldValuesBuilder(SegmentOperatorQueryBuilderEvent $event): void
    {
        if (!$event->operatorIsOneOf(self::WITHIN_VALUES)) {
            return;
        }

        $customObjectId = $this->getCustomObjectId($event->getFilter()->getParameterValue());
        $contactField   = $event->getFilter()->getField();

        $event->getQueryBuilder()->innerJoin(
            'l',
            MAUTIC_TABLE_PREFIX.'custom_item',
            'ci',
            "ci.custom_object_id = {$customObjectId} AND ci.name = l.{$contactField} AND ci.is_published = 1"
        );

        $event->setOperatorHandled(true);
        $event->stopPropagation();
    }

    /**
     * @throws NotFoundException
     */
    private function getCustomObjectId(string $filter): int
    {
        $matches = [];

        if (preg_match('/custom-object:(\d*):name/', $filter, $matches)) {
            return (int) $matches[1];
        }

        throw new NotFoundException("{$filter} is not a custom item name");
    }
}
