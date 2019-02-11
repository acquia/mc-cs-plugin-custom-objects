<?php

/*
 * @copyright   2019 Mautic, Inc. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.com
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\CustomObjectsBundle\EventListener;

use Mautic\CoreBundle\EventListener\CommonSubscriber;
use Mautic\LeadBundle\LeadEvents;
use Mautic\LeadBundle\Event\ImportInitEvent;
use Mautic\LeadBundle\Event\ImportMappingEvent;
use Mautic\LeadBundle\Event\ImportProcessEvent;
use MauticPlugin\CustomObjectsBundle\Exception\NotFoundException;
use MauticPlugin\CustomObjectsBundle\Model\CustomObjectModel;
use MauticPlugin\CustomObjectsBundle\Model\CustomItemModel;
use MauticPlugin\CustomObjectsBundle\Provider\CustomItemRouteProvider;

class ImportSubscriber extends CommonSubscriber
{
    /**
     * @var CustomObjectModel
     */
    private $customObjectModel;

    /**
     * @var CustomItemModel
     */
    private $customItemModel;

    /**
     * @param CustomObjectModel $customObjectModel
     * @param CustomItemModel $customItemModel
     */
    public function __construct(
        CustomObjectModel $customObjectModel,
        CustomItemModel $customItemModel
    )
    {
        $this->customObjectModel = $customObjectModel;
        $this->customItemModel   = $customItemModel;
    }

    /**
     * @return array
     */
    public static function getSubscribedEvents(): array
    {
        return [
            LeadEvents::IMPORT_ON_INITIALIZE    => 'onImportInit',
            LeadEvents::IMPORT_ON_FIELD_MAPPING => 'onFieldMapping',
            LeadEvents::IMPORT_ON_PROCESS       => 'onImportProcess',
        ];
    }

    /**
     * @param ImportInitEvent $event
     */
    public function onImportInit(ImportInitEvent $event): void
    {
        try {
            $customObjectId = $this->getCustomObjectId($event->getRouteObjectName());
            $customObject   = $this->customObjectModel->fetchEntity($customObjectId);
            $event->setObjectIsSupported(true);
            $event->setObjectSingular($event->getRouteObjectName());
            $event->setObjectName($customObject->getNamePlural());
            $event->setActiveLink("#mautic_custom_object_{$customObjectId}");
            $event->setIndexRoute(CustomItemRouteProvider::ROUTE_LIST, ['objectId' => $customObjectId]);
            $event->stopPropagation();
        } catch (NotFoundException $e) {}
    }

    /**
     * @param ImportMappingEvent $event
     */
    public function onFieldMapping(ImportMappingEvent $event): void
    {
        try {
            $customObjectId = $this->getCustomObjectId($event->getRouteObjectName());
            $customObject   = $this->customObjectModel->fetchEntity($customObjectId);
            $specialFields  = [
                'linkedContactIds' => 'custom.item.link.contact.ids',
            ];

            $fieldList = ['customItemName' => 'custom.item.name.label'];
            $customFields = $customObject->getCustomFields();

            foreach ($customFields as $customField) {
                $fieldList[$customField->getId()] = $customField->getName();
            }

            $event->setFields([
                $customObject->getNamePlural() => $fieldList,
                'mautic.lead.special_fields'   => $specialFields,
            ]);
        } catch (NotFoundException $e) {}
    }

    /**
     * @param ImportProcessEvent $event
     */
    public function onImportProcess(ImportProcessEvent $event): void
    {
        try {
            $customObjectId = $this->getCustomObjectId($event->getImport()->getObject());
            $customObject   = $this->customObjectModel->fetchEntity($customObjectId);
            $import         = $event->getImport();
            $merged         = $this->customItemModel->import($import, $event->getRowData(), $customObject);
            $event->setWasMerged($merged);
        } catch (NotFoundException $e) {}
    }

    /**
     * @param string $routeObjectName
     * 
     * @return integer
     * 
     * @throws NotFoundException
     */
    private function getCustomObjectId(string $routeObjectName): int
    {
        if (preg_match('/custom-object:(\d)/', $routeObjectName, $matches)) {
            return (int) $matches[1];
        }

        throw new NotFoundException("{$routeObjectName} is not a custom object import");
    }
}
