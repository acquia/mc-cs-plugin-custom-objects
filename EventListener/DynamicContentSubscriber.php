<?php

declare(strict_types=1);

namespace MauticPlugin\CustomObjectsBundle\EventListener;

use Mautic\DynamicContentBundle\DynamicContentEvents;
use Mautic\DynamicContentBundle\Event\ContactFiltersEvaluateEvent;
use MauticPlugin\CustomObjectsBundle\Exception\InvalidSegmentFilterException;
use MauticPlugin\CustomObjectsBundle\Helper\ContactFilterMatcher;
use MauticPlugin\CustomObjectsBundle\Provider\ConfigProvider;
use MauticPlugin\CustomObjectsBundle\Segment\Query\Filter\QueryFilterFactory;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class DynamicContentSubscriber implements EventSubscriberInterface
{
    private QueryFilterFactory $queryFilterFactory;
    private ConfigProvider $configProvider;
    private ContactFilterMatcher $contactFilterMatcher;

    public function __construct(
        QueryFilterFactory $queryFilterFactory,
        ConfigProvider $configProvider,
        ContactFilterMatcher $contactFilterMatcher
    ) {
        $this->queryFilterFactory   = $queryFilterFactory;
        $this->configProvider       = $configProvider;
        $this->contactFilterMatcher = $contactFilterMatcher;
    }

    /**
     * @return array<string,array{string,int}>
     */
    public static function getSubscribedEvents(): array
    {
        return [
            DynamicContentEvents::ON_CONTACTS_FILTER_EVALUATE => ['evaluateFilters', 0],
        ];
    }

    public function evaluateFilters(ContactFiltersEvaluateEvent $event): void
    {
        // 2. Normalize only Custom Object filters
        $filters = $this->normalizeCustomObjectFilters($event->getFilters());
        if ($event->isEvaluated()
            || !$this->configProvider->pluginIsEnabled()
            || !$this->hasCustomObjectFilters($filters)
        ) {
            return;
        }

        $event->setIsEvaluated(true);
        $event->stopPropagation();
        $event->setIsMatched($this->contactFilterMatcher->match(
            $filters,
            $event->getContact()->getProfileFields()
        ));
    }

    /**
     * @param mixed[] $filters
     */
    private function hasCustomObjectFilters(array $filters): bool
    {
        foreach ($filters as $filter) {
            try {
                $this->queryFilterFactory->configureQueryBuilderFromSegmentFilter($filter, 'filter');

                return true;
            } catch (InvalidSegmentFilterException $e) {
            }
        }

        return false;
    }

    /**
     * @param array<int, array<string, mixed>> $filters
     *
     * @return array<int, array<string, mixed>>
     */
    private function normalizeCustomObjectFilters(array $filters): array
    {
        foreach ($filters as &$filter) {
            if (
                !isset($filter['properties']['options']) ||
                !is_array($filter['properties']['options'])
            ) {
                continue;
            }

            $label = $filter['filter_value'] ?? null;
            if (!$label) {
                continue;
            }

            $options = $filter['properties']['options'];

            // Case A: Standard format (label => value)
            if (isset($options[$label])) {
                $filter['filter_value'] = $options[$label];
                continue;
            }

            // Case B: Reverse format (value => label)
            $reversed = array_flip($options);
            if (isset($reversed[$label])) {
                $filter['filter_value'] = $reversed[$label];
            }
        }

        return $filters;
    }
}
