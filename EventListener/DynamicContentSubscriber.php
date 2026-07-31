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
    public function __construct(
        private QueryFilterFactory $queryFilterFactory,
        private ContactFilterMatcher $contactFilterMatcher,
        private ConfigProvider $configProvider,
    ) {
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
        if ($event->isEvaluated()
            || !$this->configProvider->pluginIsEnabled()
            || !$this->hasCustomObjectFilters($event->getFilters())
        ) {
            return;
        }

        $event->setIsEvaluated(true);
        $event->stopPropagation();
        $contact = $event->getContact();
        $event->setIsMatched($this->contactFilterMatcher->match(
            $event->getFilters(),
            array_merge(['id' => $contact->getId()], $contact->getProfileFields())
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
            } catch (InvalidSegmentFilterException) {
            }
        }

        return false;
    }
}
