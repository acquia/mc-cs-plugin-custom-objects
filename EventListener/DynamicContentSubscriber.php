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
use Mautic\LeadBundle\Segment\OperatorOptions;

use MauticPlugin\CustomObjectsBundle\Helper\QueryFilterHelper;


class DynamicContentSubscriber extends CommonSubscriber
{
    use MatchFilterForLeadTrait, QueryFilterHelper;

    /**
     * @var EntityManager
     */
    private $entityManager;


    public function __construct(EntityManager $entityManager)
    {
        $this->entityManager        = $entityManager;
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
            if ($eventFilter['object'] != 'custom_object') {
                continue;
            }
            if (!$isCustomFieldValueFilter = preg_match('/^cmf_([0-9]+)$/', $eventFilter['field'], $matches)) {
                $isCustomObjectNameFilter = preg_match('/^cmo_([0-9]+)$/', $eventFilter['field'], $matches);
            }

            $operator = OperatorOptions::getFilterExpressionFunctions()[$eventFilter['operator']]['expr'];

            if ($isCustomFieldValueFilter) {
                $tableAlias        = 'cfwq_' . (int) $matches[1] . '';
                $valueQueryBuilder = $this->createValueQueryBuilder($connection, $tableAlias, (int) $matches[1], $eventFilter['type']);
                $this->addCustomFieldValueExpression($valueQueryBuilder, $tableAlias, $operator, $eventFilter['filter']);
            } elseif ($isCustomObjectNameFilter) {
                $tableAlias        = 'cowq_' . (int) $matches[1] . '';
                $nameQueryBuilder = $this->createItemNameQueryBuilder($connection, $tableAlias);
                $this->addCustomFieldValueExpression($nameQueryBuilder, $tableAlias, $operator, $eventFilter['filter']);
            } else {
                throw new \Exception('Not implemented');
            }

            $this->addContactIdRestriction($valueQueryBuilder, $tableAlias, (int) $event->getContact()->getId());

            MauticDebugHelper::dumpSQL($valueQueryBuilder);

            if ($valueQueryBuilder->execute()->rowCount()) {
                $event->setIsEvaluated(true);
                $event->setIsMatched(true);
            } else {
                $event->setIsEvaluated(true);
            }
            $event->stopPropagation();  // The filter is ours, we won't allow no more processing
        }
    }
}
