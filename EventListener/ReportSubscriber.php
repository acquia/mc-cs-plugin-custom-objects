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
    const CUSTOM_ITEM_TABLE_ALIAS = 'ci';
    const CUSTOM_ITEM_TABLE_PREFIX = self::CUSTOM_ITEM_TABLE_ALIAS . '.';
    const CUSTOM_OBJECTS_CONTEXT = 'custom.objects';

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
        return static::CUSTOM_OBJECTS_CONTEXT . '.' . $customObject->getId();
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
            $event->getStandardColumns(static::CUSTOM_ITEM_TABLE_PREFIX, ['description', 'publish_up', 'publish_down'])
        );

        /** @var CustomObject $customObject */
        foreach ($this->getCustomObjects() as $customObject) {
            $event->addTable(
                $this->getContext($customObject),
                [
                    'display_name' => $customObject->getNamePlural(),
                    'columns' => $columns,
                ],
                static::CUSTOM_OBJECTS_CONTEXT
            );
        }
    }

    private function getCustomItemColumns(): array
    {
        $columns = [
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

        $customObjectId = (int)preg_replace('/[^\d]/', '', $event->getContext());
        if (1 > $customObjectId) {
            throw new \RuntimeException('Custom Object is not defined.');
        }

        $queryBuilder = $event->getQueryBuilder();
        $queryBuilder
            ->from($this->customItemRepository->getTableName(), static::CUSTOM_ITEM_TABLE_ALIAS)
            ->andWhere(static::CUSTOM_ITEM_TABLE_PREFIX . 'custom_object_id = :customObjectId')
            ->setParameter('customObjectId', $customObjectId);
    }
}
