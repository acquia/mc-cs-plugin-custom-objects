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
use Mautic\CoreBundle\CoreEvents;
use Mautic\CoreBundle\Event\PermissionsEvent;
use MauticPlugin\CustomObjectsBundle\Security\Permissions\CustomObjectPermissions;
use MauticPlugin\CustomObjectsBundle\Model\CustomObjectModel;
use MauticPlugin\CustomObjectsBundle\Provider\ConfigProvider;

class PermissionsSubscriber extends CommonSubscriber
{
    /**
     * @var CustomObjectModel
     */
    private $customObjectModel;

    /**
     * @var ConfigProvider
     */
    private $configProvider;

    /**
     *
     * @param CustomObjectModel $customObjectModel
     * @param ConfigProvider    $configProvider
     */
    public function __construct(CustomObjectModel $customObjectModel, ConfigProvider $configProvider)
    {
        $this->customObjectModel = $customObjectModel;
        $this->configProvider    = $configProvider;
    }

    /**
     * @return array
     */
    public static function getSubscribedEvents(): array
    {
        return [
            CoreEvents::BUILD_PERMISSIONS => 'onPermissionsBuild',
        ];
    }

    /**
     * @param PermissionsEvent $event
     */
    public function onPermissionsBuild(PermissionsEvent $event): void
    {
        if ($this->configProvider->pluginIsEnabled() && CustomObjectPermissions::class === $event->getBundle()) {
            $permissionsObject = new CustomObjectPermissions($this->customObjectModel, $event->getParams());
            $event->setPermissionsObject($permissionsObject);
        }
    }
}
