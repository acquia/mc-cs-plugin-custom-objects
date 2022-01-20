<?php

declare(strict_types=1);

namespace MauticPlugin\CustomObjectsBundle\EventListener;

use Mautic\LeadBundle\Event\FieldOperatorsEvent;
use Mautic\LeadBundle\Event\FormAdjustmentEvent;
use Mautic\LeadBundle\Event\LeadListFiltersChoicesEvent;
use Mautic\LeadBundle\Event\LeadListFiltersOperatorsEvent;
use Mautic\LeadBundle\Event\SegmentOperatorQueryBuilderEvent;
use Mautic\LeadBundle\LeadEvents;
use MauticPlugin\CustomObjectsBundle\Exception\NotFoundException;
use MauticPlugin\CustomObjectsBundle\Model\CustomObjectModel;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;

class FilterOperatorSubscriber implements EventSubscriberInterface
{
    public const WITHIN_CUSTOM_OBJECTS = 'withinCustomObjects';

    public const NOT_IN_CUSTOM_OBJECTS = 'notInCustomObjects';

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
            LeadEvents::LIST_FILTERS_CHOICES_ON_GENERATE               => ['addNotInCustomObjectsOperatorForEmailType', -9999],
        ];
    }

    public function onOperatorsGenerate(LeadListFiltersOperatorsEvent $event)
    {
        $event->addOperator(self::WITHIN_CUSTOM_OBJECTS, [
            'label'       => 'custom.within.custom.objects.label',
            'expr'        => self::WITHIN_CUSTOM_OBJECTS,
            'negate_expr' => 'notWithinCustomObjects',
        ]);
    }

    public function addWithinFieldValuesOperator(FieldOperatorsEvent $event): void
    {
        if ('generated_email_domain' === $event->getField()) {
            $event->addOperator(self::WITHIN_CUSTOM_OBJECTS);
        }
    }

    public function addNotInCustomObjectsOperatorForEmailType(LeadListFiltersChoicesEvent $event): void
    {
        $config                      = $event->getChoices()['lead']['email'];
        $label                       = $event->getTranslator()->trans('custom.not_in.custom.objects.label');
        $config['operators'][$label] = self::NOT_IN_CUSTOM_OBJECTS;
        $event->setChoice('lead', 'email', $config);
    }

    public function onSegmentFilterFormHandleWithinFieldFormType(FormAdjustmentEvent $event): void
    {
        if (!$event->operatorIsOneOf(self::WITHIN_CUSTOM_OBJECTS, self::NOT_IN_CUSTOM_OBJECTS)) {
            return;
        }

        $form          = $event->getForm();
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
        if (!$event->operatorIsOneOf(self::WITHIN_CUSTOM_OBJECTS, self::NOT_IN_CUSTOM_OBJECTS)) {
            return;
        }

        $leadsTableAlias = $event->getLeadsTableAlias();

        $customObjectId = $this->getCustomObjectId($event->getFilter()->getParameterValue());
        $contactField   = $event->getFilter()->getField();

        if ($event->operatorIsOneOf(self::WITHIN_CUSTOM_OBJECTS)) {
            $event->getQueryBuilder()->innerJoin(
                $leadsTableAlias,
                MAUTIC_TABLE_PREFIX.'custom_item',
                'ci',
                "ci.custom_object_id = {$customObjectId} AND ci.name = $leadsTableAlias.{$contactField} AND ci.is_published = 1"
            );
        } elseif ($event->operatorIsOneOf(self::NOT_IN_CUSTOM_OBJECTS)) {
            $queryBuilder           = $event->getQueryBuilder();
            $subQueryBuilder        = $queryBuilder->getConnection()->createQueryBuilder();
            $expr                   = $subQueryBuilder->expr();
            $customItemQueryBuilder = $subQueryBuilder->select('ci.name')
                ->from(MAUTIC_TABLE_PREFIX.'custom_item', 'ci')
                ->andWhere($expr->eq('ci.custom_object_id', $customObjectId))
                ->andWhere($expr->eq('ci.is_published', 1));

            $queryBuilder->andWhere($expr->notIn($leadsTableAlias.'.'.$contactField, $customItemQueryBuilder->getSQL()));
        }

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
