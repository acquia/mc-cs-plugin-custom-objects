<?php

declare(strict_types=1);

namespace MauticPlugin\CustomObjectsBundle\EventListener;

use Mautic\LeadBundle\Event\LeadListMergeFiltersEvent;
use Mautic\LeadBundle\Segment\ContactSegmentFilterFactory;
use MauticPlugin\CustomObjectsBundle\Provider\ConfigProvider;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class SegmentFiltersMergeSubscriber implements EventSubscriberInterface
{
    private ConfigProvider $configProvider;

    public function __construct(ConfigProvider $configProvider)
    {
        $this->configProvider = $configProvider;
    }

    /**
     * @return mixed[]
     */
    public static function getSubscribedEvents(): array
    {
        return [
            /**
             * Using string instead of constant for now to avoid issues in custom object plugin.
             * When \Mautic\LeadBundle\LeadEvents::LIST_FILTERS_MERGE is available in mautic\mautic,
             * we can use it here instead of string.
             */
            // \Mautic\LeadBundle\LeadEvents::LIST_FILTERS_MERGE => 'mergeCustomObjectFilters'
            'mautic.list_filters_merge' => 'mergeCustomObjectFilters',
        ];
    }

    public function mergeCustomObjectFilters(LeadListMergeFiltersEvent $event): void
    {
        if (!$this->configProvider->pluginIsEnabled() || (!$this->configProvider->isCustomObjectMergeFilterEnabled())) {
            return;
        }

        $filters = $event->getFilters();

        $finalMergedFilters = [];
        $customFieldArr     = [];
        $customObjectIndex  = null;
        foreach ($filters as $index => $filter) {
            $glue = $filter['glue'];
            if ('or' === strtolower($glue) && !empty($customFieldArr)) {
                $finalMergedFilters = array_merge($finalMergedFilters, $this->groupCustomObject($customFieldArr));
                $customObjectIndex  = null;
                $customFieldArr     = [];
            }

            if ('custom_object' !== ($filter['object'] ?? '')) {
                $finalMergedFilters[] = $filter;
                continue;
            }
            if (!$customObjectIndex) {
                $customObjectIndex = $index;
            }
            $key                          = implode('_', [$filter['object'], $filter['glue']]);
            $customFieldArr[$key][$index] = $filter;
        }
        if (!empty($customFieldArr)) {
            $finalMergedFilters = array_merge($finalMergedFilters, $this->groupCustomObject($customFieldArr));
        }
        $event->setFilters($finalMergedFilters);
    }

    /**
     * @param array<mixed> $customObjectFilters
     *
     * @return array<mixed>
     */
    private function groupCustomObject(array $customObjectFilters): array
    {
        $newGroupedArr = [];
        foreach ($customObjectFilters as $customObjects) {
            $key                             = key($customObjects);
            $newGroupedArr[$key]             = $customObjects[$key];
            $newGroupedArr[$key]['operator'] = ContactSegmentFilterFactory::CUSTOM_OPERATOR;
            unset($newGroupedArr[$key]['filter']);
            $mergedProperty = [];

            foreach ($customObjects as $filter) {
                $mergedProperty[] = [
                    'operator'     => $filter['operator'],
                    'filter_value' => $filter['properties']['filter'],
                    'field'        => $filter['field'],
                    'cmo_filter'   => str_starts_with($filter['field'], 'cmo_'),
                ];
                $newGroupedArr[$key]['properties'][] = $filter;
            }

            unset($newGroupedArr[$key]['properties']['filter']);
            $newGroupedArr[$key]['merged_property'] = $mergedProperty;
        }

        return $newGroupedArr;
    }
}
