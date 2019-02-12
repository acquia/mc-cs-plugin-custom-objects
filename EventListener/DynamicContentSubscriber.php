<?php
declare(strict_types=1);

/*
 * @copyright   2019 Mautic Contributors. All rights reserved
 * @author      Mautic Inc.
 *
 * @link        http://mautic.com
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\CustomObjectsBundle\EventListener;

use Doctrine\ORM\EntityManager;
use Mautic\CoreBundle\EventListener\CommonSubscriber;
use Mautic\DebugBundle\Service\MauticDebugHelper;
use Mautic\DynamicContentBundle\DynamicContentEvents;
use Mautic\EmailBundle\Event\ContactFiltersEvaluateEvent;
use Mautic\EmailBundle\EventListener\MatchFilterForLeadTrait;
use Mautic\LeadBundle\Segment\ContactSegmentFilterCrate;
use Mautic\LeadBundle\Segment\ContactSegmentFilterFactory;
use Mautic\LeadBundle\Segment\Decorator\DecoratorFactory;
use Mautic\LeadBundle\Segment\OperatorOptions;

use MauticPlugin\CustomObjectsBundle\Helper\QueryFilterHelper;


class DynamicContentSubscriber extends CommonSubscriber
{
    use MatchFilterForLeadTrait, QueryFilterHelper;

    /**
     * @var EntityManager
     */
    private $entityManager;
    /**
     * @var ContactSegmentFilterFactory
     */
    private $filterFactory;


    public function __construct(EntityManager $entityManager, ContactSegmentFilterFactory $filterFactory)
    {
        $this->entityManager = $entityManager;
        $this->filterFactory = $filterFactory;
    }

    /**
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return [
            DynamicContentEvents::ON_CONTACTS_FILTER_EVALUATE => ['evaluateFilters', 0],
        ];
    }

    public function evaluateFilters(ContactFiltersEvaluateEvent $event)
    {
        $eventFilters = $event->getFilters();
        if ($event->isEvaluated()) {
            return;
        }

        $connection = $this->entityManager->getConnection();

        foreach ($eventFilters as $eventFilter) {
            $segmentFilter = $this->filterFactory->factorSegmentFilter($eventFilter);

            if ($segmentFilter->getTable() != MAUTIC_TABLE_PREFIX.'custom_objects') {
                continue;
            }

            if ($segmentFilter->getQueryType()=='mautic.lead.query.builder.custom_field.value') {
                $tableAlias        = 'cfwq_' . (int) $segmentFilter->getField();
                $filterQueryBuilder = $this->createValueQueryBuilder(
                    $connection,
                    $tableAlias,
                    (int) $segmentFilter->getField(),
                    $segmentFilter->getType()
                );
                $this->addCustomFieldValueExpressionFromSegmentFilter($filterQueryBuilder, $tableAlias, $segmentFilter);
            } elseif ($segmentFilter->getQueryType()=='mautic.lead.query.builder.custom_item.value') {
                $tableAlias       = 'cowq_' . (int) $segmentFilter->getField();
                $filterQueryBuilder = $this->createItemNameQueryBuilder($connection, $tableAlias);
                $this->addCustomObjectNameExpression(
                    $filterQueryBuilder,
                    $tableAlias,
                    $segmentFilter->getOperator(),
                    $segmentFilter->getParameterValue()
                );
            } else {
                throw new \Exception('Not implemented');
            }

            $this->addContactIdRestriction($filterQueryBuilder, $tableAlias, (int) $event->getContact()->getId());

            MauticDebugHelper::dumpSQL($filterQueryBuilder);

            if ($filterQueryBuilder->execute()->rowCount()) {
                $event->setIsEvaluated(true);
                $event->setIsMatched(true);
            } else {
                $event->setIsEvaluated(true);
            }
            $event->stopPropagation();  // The filter is ours, we won't allow no more processing
        }
    }
}
