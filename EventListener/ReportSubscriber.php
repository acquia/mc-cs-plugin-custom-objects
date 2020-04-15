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
use MauticPlugin\CustomObjectsBundle\Entity\CustomObject;
use MauticPlugin\CustomObjectsBundle\Repository\CustomObjectRepository;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class ReportSubscriber implements EventSubscriberInterface
{
    const PREFIX = 'co.';
    const CONTEXT_CUSTOM_OBJECTS = 'custom.objects';

    private static $customObjects = null;

    /**
     * @var CustomObjectRepository
     */
    private $repository;

    public function __construct(CustomObjectRepository $repository)
    {
        $this->repository = $repository;
    }

    private function getCustomObjects(): array
    {
        if (null !== self::$customObjects) {
            return self::$customObjects;
        }

        self::$customObjects = $this->repository->findAll();
        return self::$customObjects;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            ReportEvents::REPORT_ON_BUILD => ['onReportBuilder', 0],
        ];
    }

    public function onReportBuilder(ReportBuilderEvent $event): void
    {
        $callback = function(CustomObject $customObject) {
            return static::CONTEXT_CUSTOM_OBJECTS . '.' . $customObject->getId();
        };

        if (!$event->checkContext(array_map($callback, $this->getCustomObjects()))) {
            return;
        }

        $columns = array_merge(
            $event->getStandardColumns(static::PREFIX, []),
            $event->getCategoryColumns()
        );

        $customObjects = $this->getCustomObjects();

        /** @var CustomObject $customObject */
        foreach ($customObjects as $customObject) {
            $event->addTable(
                static::CONTEXT_CUSTOM_OBJECTS . '.' . $customObject->getId(),
                [
                    'display_name' => $customObject->getNamePlural(),
                    'columns'      => $columns,
                ],
                static::CONTEXT_CUSTOM_OBJECTS
            );
        }
    }
}
