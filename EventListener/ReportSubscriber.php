<?php

declare(strict_types=1);

/*
 * @copyright   2020 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\CustomObjectsBundle\EventListener;

use Mautic\ReportBundle\Event\ReportBuilderEvent;
use Mautic\ReportBundle\ReportEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class ReportSubscriber implements EventSubscriberInterface
{
    const PREFIX = 'co.';
    const CONTEXT_CUSTOM_OBJECTS = 'custom.objects';

    public static function getSubscribedEvents(): array
    {
        return [
            ReportEvents::REPORT_ON_BUILD => ['onReportBuilder', 0],
        ];
    }

    public function onReportBuilder(ReportBuilderEvent $event): void
    {
        $columns = array_merge(
            $event->getStandardColumns(static::PREFIX, []),
            $event->getCategoryColumns()
        );

        $event->addTable(
            static::CONTEXT_CUSTOM_OBJECTS,
            [
                'display_name' => 'custom.object.title',
                'columns'      => $columns,
            ]
        );
    }
}
