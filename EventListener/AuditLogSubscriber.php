<?php

declare(strict_types=1);

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
use MauticPlugin\CustomObjectsBundle\CustomItemEvents;
use MauticPlugin\CustomObjectsBundle\Event\CustomItemEvent;
use Mautic\CoreBundle\Model\AuditLogModel;
use Mautic\CoreBundle\Helper\IpLookupHelper;
use MauticPlugin\CustomObjectsBundle\Event\CustomObjectEvent;
use MauticPlugin\CustomObjectsBundle\CustomObjectEvents;

class AuditLogSubscriber extends CommonSubscriber
{
    /**
     * @var AuditLogModel
     */
    private $auditLogModel;

    /**
     * @var IpLookupHelper
     */
    private $ipLookupHelper;

    /**
     * @param AuditLogModel $auditLogModel
     * @param IpLookupHelper $ipLookupHelper
     */
    public function __construct(AuditLogModel $auditLogModel, IpLookupHelper $ipLookupHelper)
    {
        $this->auditLogModel  = $auditLogModel;
        $this->ipLookupHelper = $ipLookupHelper;
    }

    /**
     * @return array
     */
    public static function getSubscribedEvents(): array
    {
        return [
            CustomItemEvents::ON_CUSTOM_ITEM_POST_SAVE       => 'onCustomItemPostSave',
            CustomItemEvents::ON_CUSTOM_ITEM_POST_DELETE     => 'onCustomItemPostDelete',
            CustomObjectEvents::ON_CUSTOM_OBJECT_POST_SAVE   => 'onCustomObjectPostSave',
            CustomObjectEvents::ON_CUSTOM_OBJECT_POST_DELETE => 'onCustomObjectPostDelete',
        ];
    }

    /**
     * Add a create/update entry to the audit log.
     *
     * @param CustomItemEvent $event
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
     *
     * @param CustomItemEvent $event
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
     *
     * @param CustomObjectEvent $event
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
     *
     * @param CustomObjectEvent $event
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
}
