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
use Mautic\ReportBundle\Event\ReportGeneratorEvent;
use Mautic\ReportBundle\ReportEvents;
use MauticPlugin\CustomObjectsBundle\Entity\CustomObject;
use MauticPlugin\CustomObjectsBundle\Repository\CustomItemRepository;
use MauticPlugin\CustomObjectsBundle\Repository\CustomObjectRepository;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class ReportSubscriber implements EventSubscriberInterface
{
    const PREFIX = 'ci';
    const CONTEXT_CUSTOM_OBJECTS = 'custom.objects';

    private static $customObjects = null;

    /**
     * @var CustomObjectRepository
     */
    private $customObjectRepository;

    /**
     * @var CustomItemRepository
     */
    private $customItemRepository;

    public function __construct(CustomObjectRepository $customObjectRepository, CustomItemRepository $customItemRepository)
    {
        $this->customObjectRepository = $customObjectRepository;
        $this->customItemRepository = $customItemRepository;
    }

    private function getCustomObjects(): array
    {
        if (null !== static::$customObjects) {
            return static::$customObjects;
        }

        static::$customObjects = $this->customObjectRepository->findAll();
        usort(static::$customObjects, function (CustomObject $a, CustomObject $b): int {
            return strnatcmp($a->getNamePlural(), $b->getNamePlural());
        });

        return static::$customObjects;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            ReportEvents::REPORT_ON_BUILD => ['onReportBuilder', 0],
            ReportEvents::REPORT_ON_GENERATE => ['onReportGenerate', 0],
        ];
    }

    private function getContext(CustomObject $customObject): string
    {
        return static::CONTEXT_CUSTOM_OBJECTS . '.' . $customObject->getId();
    }

    public function getContexts(): array
    {
        return array_map([$this, 'getContext'], $this->getCustomObjects());
    }

    public function onReportBuilder(ReportBuilderEvent $event): void
    {
        if (!$event->checkContext($this->getContexts())) {
            return;
        }

        $columns = array_merge(
            $this->getCustomItemColumns(),
            []
        );

        /** @var CustomObject $customObject */
        foreach ($this->getCustomObjects() as $customObject) {
            $event->addTable(
                $this->getContext($customObject),
                [
                    'display_name' => $customObject->getNamePlural(),
                    'columns' => $columns,
                ],
                static::CONTEXT_CUSTOM_OBJECTS
            );
        }
    }

    private function getCustomItemColumns(): array
    {
        $prefix = static::PREFIX . '.';

        $columns = [
            $prefix . 'name' => [
                'label' => 'custom.item.name.label',
                'type' => 'string',
            ]
        ];

        return $columns;
    }

    /**
     * Initialize the QueryBuilder object to generate reports from.
     */
    public function onReportGenerate(ReportGeneratorEvent $event)
    {
        if (!$event->checkContext($this->getContexts())) {
            return;
        }

        $queryBuilder = $event->getQueryBuilder();
        $queryBuilder->from($this->customItemRepository->getTableName(), static::PREFIX);
    }
}
