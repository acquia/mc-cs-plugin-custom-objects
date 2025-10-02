<?php

declare(strict_types=1);

namespace MauticPlugin\CustomObjectsBundle\EventListener;

use Mautic\DynamicContentBundle\DynamicContentEvents;
use Mautic\DynamicContentBundle\Event\ContactFiltersEvaluateEvent;
use MauticPlugin\CustomObjectsBundle\Exception\InvalidSegmentFilterException;
use MauticPlugin\CustomObjectsBundle\Helper\ContactFilterMatcher;
use MauticPlugin\CustomObjectsBundle\Provider\ConfigProvider;
use MauticPlugin\CustomObjectsBundle\Segment\Query\Filter\QueryFilterFactory;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class DynamicContentSubscriber implements EventSubscriberInterface
{
    use MatchFilterForLeadTrait;
    use DbalQueryTrait;

    public function __construct(
        private QueryFilterFactory $queryFilterFactory,
        private QueryFilterHelper $queryFilterHelper,
        private ConfigProvider $configProvider,
        private LoggerInterface $logger
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
        $event->setIsMatched($this->contactFilterMatcher->match(
            $event->getFilters(),
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

            $this->queryFilterHelper->addContactIdRestriction($filterQueryBuilder, $queryAlias, (int) $event->getContact()->getId());

            try {
                if ($this->executeSelect($filterQueryBuilder)->rowCount()) {
                    $event->setIsEvaluated(true);
                    $event->setIsMatched(true);
                } else {
                    $event->setIsEvaluated(true);
                }
            } catch (\PDOException $e) {
                $this->logger->error('Failed to evaluate dynamic content for custom object '.$e->getMessage());

                throw $e;
            }

            $event->stopPropagation();  // The filter is ours, we won't allow no more processing
        }

        return false;
    }
}
