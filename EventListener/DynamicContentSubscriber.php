<?php

declare(strict_types=1);

namespace MauticPlugin\CustomObjectsBundle\EventListener;

use Mautic\DynamicContentBundle\DynamicContentEvents;
use Mautic\DynamicContentBundle\Event\ContactFiltersEvaluateEvent;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Entity\Tag;
use Mautic\LeadBundle\Helper\PrimaryCompanyHelper;
use MauticPlugin\CustomObjectsBundle\Exception\InvalidSegmentFilterException;
use MauticPlugin\CustomObjectsBundle\Helper\ContactFilterMatcher;
use MauticPlugin\CustomObjectsBundle\Provider\ConfigProvider;
use MauticPlugin\CustomObjectsBundle\Segment\Query\Filter\QueryFilterFactory;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class DynamicContentSubscriber implements EventSubscriberInterface
{
    private QueryFilterFactory $queryFilterFactory;
    private ConfigProvider $configProvider;
    private PrimaryCompanyHelper $primaryCompanyHelper;
    private ContactFilterMatcher $contactFilterMatcher;

    public function __construct(
        QueryFilterFactory $queryFilterFactory,
        ConfigProvider $configProvider,
        PrimaryCompanyHelper $primaryCompanyHelper,
        ContactFilterMatcher $contactFilterMatcher
    ) {
        $this->queryFilterFactory   = $queryFilterFactory;
        $this->configProvider       = $configProvider;
        $this->primaryCompanyHelper = $primaryCompanyHelper;
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
        if ($event->isEvaluated()
            || !$this->configProvider->pluginIsEnabled()
            || !$this->hasCustomObjectFilters($event->getFilters())
        ) {
            return;
        }

        $event->setIsEvaluated(true);
        $event->stopPropagation();
        $event->setIsMatched($this->doesFiltersMatch($event->getFilters(), $event->getContact()));
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
     * @param mixed[] $filters
     */
    private function doesFiltersMatch(array $filters, Lead $contact): bool
    {
        $lead = $this->primaryCompanyHelper->getProfileFieldsWithPrimaryCompany($contact);
        $lead = array_merge($lead, [
            'tags' => array_map(
                function (Tag $v) {
                    return $v->getId();
                },
                $contact->getTags()->toArray()
            ),
        ]);

        return $this->contactFilterMatcher->match($filters, $lead);
    }
}
