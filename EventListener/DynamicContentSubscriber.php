<?php

declare(strict_types=1);

namespace MauticPlugin\CustomObjectsBundle\EventListener;

use Mautic\DynamicContentBundle\DynamicContentEvents;
use Mautic\DynamicContentBundle\Event\ContactFiltersEvaluateEvent;
use Mautic\EmailBundle\EventListener\MatchFilterForLeadTrait;
use MauticPlugin\CustomObjectsBundle\Exception\InvalidArgumentException;
use MauticPlugin\CustomObjectsBundle\Exception\InvalidSegmentFilterException;
use MauticPlugin\CustomObjectsBundle\Exception\NotFoundException;
use MauticPlugin\CustomObjectsBundle\Helper\QueryFilterHelper;
use MauticPlugin\CustomObjectsBundle\Provider\ConfigProvider;
use MauticPlugin\CustomObjectsBundle\Repository\DbalQueryTrait;
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
     * @return mixed[]
     */
    public static function getSubscribedEvents(): array
    {
        return [
            DynamicContentEvents::ON_CONTACTS_FILTER_EVALUATE => ['evaluateFilters', 0],
        ];
    }

    /**
     * @throws InvalidArgumentException
     * @throws NotFoundException
     */
    public function evaluateFilters(ContactFiltersEvaluateEvent $event): void
    {
        if (!$this->configProvider->pluginIsEnabled()) {
            return;
        }

        $eventFilters = $event->getFilters();

        if ($event->isEvaluated()) {
            return;
        }

        foreach ($eventFilters as $key => $eventFilter) {
            $queryAlias = "filter_{$key}";

            try {
                $filterQueryBuilder = $this->queryFilterFactory->configureQueryBuilderFromSegmentFilter($eventFilter, $queryAlias);
            } catch (InvalidSegmentFilterException $e) {
                continue;
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
    }
}
