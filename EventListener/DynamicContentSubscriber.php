<?php

declare(strict_types=1);

/*
 * @copyright   2019 Mautic Contributors. All rights reserved
 * @author      Mautic Inc.
 *
 * @link        https://mautic.com
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\CustomObjectsBundle\EventListener;

use Doctrine\ORM\EntityManager;
use Mautic\CoreBundle\EventListener\CommonSubscriber;
use Mautic\DynamicContentBundle\DynamicContentEvents;
use Mautic\DynamicContentBundle\Event\ContactFiltersEvaluateEvent;
use Mautic\EmailBundle\EventListener\MatchFilterForLeadTrait;
use Mautic\LeadBundle\Segment\ContactSegmentFilterFactory;
use MauticPlugin\CustomObjectsBundle\Helper\QueryFilterHelper;
use MauticPlugin\CustomObjectsBundle\Repository\DbalQueryTrait;

class DynamicContentSubscriber extends CommonSubscriber
{
    use MatchFilterForLeadTrait;
    use DbalQueryTrait;

    /**
     * @var EntityManager
     */
    private $entityManager;

    /**
     * @var ContactSegmentFilterFactory
     */
    private $filterFactory;

    /**
     * @var QueryFilterHelper
     */
    private $queryHelper;

    /**
     * @param EntityManager               $entityManager
     * @param ContactSegmentFilterFactory $filterFactory
     * @param QueryFilterHelper           $queryHelper
     */
    public function __construct(
        EntityManager $entityManager,
        ContactSegmentFilterFactory $filterFactory,
        QueryFilterHelper $queryHelper)
    {
        $this->entityManager = $entityManager;
        $this->filterFactory = $filterFactory;
        $this->queryHelper   = $queryHelper;
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
     * @param ContactFiltersEvaluateEvent $event
     *
     * @throws \Doctrine\DBAL\DBALException
     * @throws \MauticPlugin\CustomObjectsBundle\Exception\InvalidArgumentException
     */
    public function evaluateFilters(ContactFiltersEvaluateEvent $event): void
    {
        $eventFilters = $event->getFilters();
        if ($event->isEvaluated()) {
            return;
        }

        $connection = $this->entityManager->getConnection();

        foreach ($eventFilters as $eventFilter) {
            $segmentFilter = $this->filterFactory->factorSegmentFilter($eventFilter);

            if ($segmentFilter->getTable() !== MAUTIC_TABLE_PREFIX.'custom_objects') {
                continue;
            }

            if ('mautic.lead.query.builder.custom_field.value' === $segmentFilter->getQueryType()) {
                $tableAlias         = 'cfwq_'.$segmentFilter->getField();
                $filterQueryBuilder = $this->queryHelper->createValueQueryBuilder(
                    $connection,
                    $tableAlias,
                    (int) $segmentFilter->getField(),
                    $segmentFilter->getType()
                );
                $this->queryHelper->addCustomFieldValueExpressionFromSegmentFilter($filterQueryBuilder, $tableAlias, $segmentFilter);
            } elseif ('mautic.lead.query.builder.custom_item.value' === $segmentFilter->getQueryType()) {
                $tableAlias         = 'cowq_'.(int) $segmentFilter->getField();
                $filterQueryBuilder = $this->queryHelper->createItemNameQueryBuilder($connection, $tableAlias);
                $this->queryHelper->addCustomObjectNameExpression(
                    $filterQueryBuilder,
                    $tableAlias,
                    $segmentFilter->getOperator(),
                    $segmentFilter->getParameterValue()
                );
            } else {
                throw new \Exception('Not implemented');
            }

            $this->queryHelper->addContactIdRestriction($filterQueryBuilder, $tableAlias, (int) $event->getContact()->getId());

            try {
                if ($this->executeSelect($filterQueryBuilder)->rowCount()) {
                    $event->setIsEvaluated(true);
                    $event->setIsMatched(true);
                } else {
                    $event->setIsEvaluated(true);
                }
            } catch (\PDOException $e) {  // just to be a little more descriptive
                $this->logger->addError('Failed to evaluate dynamic content for custom object '.$e->getMessage());

                throw $e;
            }

            $event->stopPropagation();  // The filter is ours, we won't allow no more processing
        }
    }
}
