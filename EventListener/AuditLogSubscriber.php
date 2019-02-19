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
            CustomItemEvents::ON_CUSTOM_ITEM_POST_SAVE => 'onCustomItemPostSave',
        ];
    }

    /**
     * Add an entry to the audit log.
     *
     * @param CustomItemEvent $event
     */
    public function onCustomItemPostSave(CustomItemEvent $event): void
    {
        $customItem = $event->getCustomItem();
        $changes    = $customItem->getChanges();

        if (!empty($changes)) {
            $log = [
                'bundle'    => 'customObjects',
                'object'    => 'customItem',
                'objectId'  => $customItem->getId(),
                'action'    => ($customItem->getId()) ? 'create' : 'update',
                'details'   => $changes,
                'ipAddress' => $this->ipLookupHelper->getIpAddressFromRequest(),
            ];
            $this->auditLogModel->writeToLog($log);
        }
    }
}
