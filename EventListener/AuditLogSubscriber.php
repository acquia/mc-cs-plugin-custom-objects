<?php

declare(strict_types=1);

namespace MauticPlugin\CustomObjectsBundle\EventListener;

use Mautic\CoreBundle\Helper\IpLookupHelper;
use Mautic\CoreBundle\Model\AuditLogModel;
use MauticPlugin\CustomObjectsBundle\CustomItemEvents;
use MauticPlugin\CustomObjectsBundle\CustomObjectEvents;
use MauticPlugin\CustomObjectsBundle\Event\CustomItemEvent;
use MauticPlugin\CustomObjectsBundle\Event\CustomItemExportSchedulerEvent;
use MauticPlugin\CustomObjectsBundle\Event\CustomObjectEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class AuditLogSubscriber implements EventSubscriberInterface
{
    /**
     * @var AuditLogModel
     */
    private $auditLogModel;

    /**
     * @var IpLookupHelper
     */
    private $ipLookupHelper;

    public function __construct(AuditLogModel $auditLogModel, IpLookupHelper $ipLookupHelper)
    {
        $this->auditLogModel  = $auditLogModel;
        $this->ipLookupHelper = $ipLookupHelper;
    }

    /**
     * @return mixed[]
     */
    public static function getSubscribedEvents(): array
    {
        return [
            CustomItemEvents::ON_CUSTOM_ITEM_POST_SAVE        => 'onCustomItemPostSave',
            CustomItemEvents::ON_CUSTOM_ITEM_POST_DELETE      => 'onCustomItemPostDelete',
            CustomItemEvents::CUSTOM_ITEM_PREPARE_EXPORT_FILE => 'onCustomItemPrepareExportFile',
            CustomItemEvents::CUSTOM_ITEM_MAIL_EXPORT_FILE    => 'onCustomItemMailPreparingToSend',
            CustomObjectEvents::ON_CUSTOM_OBJECT_POST_SAVE    => 'onCustomObjectPostSave',
            CustomObjectEvents::ON_CUSTOM_OBJECT_POST_DELETE  => 'onCustomObjectPostDelete',
        ];
    }

    /**
     * Add a create/update entry to the audit log.
     */
    public function onCustomItemPostSave(CustomItemEvent $event): void
    {
        $customItem = $event->getCustomItem();
        $changes    = $customItem->getChanges();

        if (!empty($changes)) {
            $this->auditLogModel->writeToLog([
                'bundle'    => 'customObjects',
                'object'    => 'customItem',
                'objectId'  => $customItem->getId(),
                'action'    => $event->entityIsNew() ? 'create' : 'update',
                'details'   => $changes,
                'ipAddress' => $this->ipLookupHelper->getIpAddressFromRequest(),
            ]);
        }
    }

    /**
     * Add a delete entry to the audit log.
     */
    public function onCustomItemPostDelete(CustomItemEvent $event): void
    {
        $customItem = $event->getCustomItem();
        $this->auditLogModel->writeToLog([
            'bundle'    => 'customObjects',
            'object'    => 'customItem',
            'objectId'  => $customItem->deletedId,
            'action'    => 'delete',
            'details'   => ['name' => $customItem->getName()],
            'ipAddress' => $this->ipLookupHelper->getIpAddressFromRequest(),
        ]);
    }

    /**
     * Add a create/update entry to the audit log.
     */
    public function onCustomObjectPostSave(CustomObjectEvent $event): void
    {
        $customObject = $event->getCustomObject();
        $changes      = $customObject->getChanges();

        if (!empty($changes)) {
            $this->auditLogModel->writeToLog([
                'bundle'    => 'customObjects',
                'object'    => 'customObject',
                'objectId'  => $customObject->getId(),
                'action'    => $event->entityIsNew() ? 'create' : 'update',
                'details'   => $changes,
                'ipAddress' => $this->ipLookupHelper->getIpAddressFromRequest(),
            ]);
        }
    }

    /**
     * Add a delete entry to the audit log.
     */
    public function onCustomObjectPostDelete(CustomObjectEvent $event): void
    {
        $customObject = $event->getCustomObject();
        $this->auditLogModel->writeToLog([
            'bundle'    => 'customObjects',
            'object'    => 'customObject',
            'objectId'  => $customObject->deletedId,
            'action'    => 'delete',
            'details'   => ['name' => $customObject->getName()],
            'ipAddress' => $this->ipLookupHelper->getIpAddressFromRequest(),
        ]);
    }

    public function onCustomItemPrepareExportFile(CustomItemExportSchedulerEvent $event): void
    {
        $this->auditLogModel->writeToLog(
            [
                'bundle'    => 'customObjects',
                'object'    => 'CustomItemExportScheduler',
                'objectId'  => $event->getCustomItemExportScheduler()->getId(),
                'action'    => 'preparingExportFile',
                'details'   => $event->getCustomItemExportScheduler()->getChanges(),
                'ipAddress' => $this->ipLookupHelper->getIpAddressFromRequest(),
            ]
        );
    }

    public function onCustomItemMailPreparingToSend(CustomItemExportSchedulerEvent $event): void
    {
        $this->auditLogModel->writeToLog(
            [
                'bundle'    => 'customObjects',
                'object'    => 'CustomItemExportScheduler',
                'objectId'  => $event->getCustomItemExportScheduler()->getId(),
                'action'    => 'sendingEmail',
                'details'   => $event->getCustomItemExportScheduler()->getChanges(),
                'ipAddress' => $this->ipLookupHelper->getIpAddressFromRequest(),
            ]
        );
    }
}
