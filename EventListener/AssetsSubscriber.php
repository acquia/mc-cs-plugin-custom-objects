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
use Mautic\CoreBundle\Templating\Helper\AssetsHelper;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use MauticPlugin\CustomObjectsBundle\CustomObjectsBundle;
use MauticPlugin\CustomObjectsBundle\Provider\ConfigProvider;

class AssetsSubscriber extends CommonSubscriber
{
    /**
     * @var AssetsHelper
     */
    private $assetHelper;

    /**
     * @var ConfigProvider
     */
    private $configProvider;

    /**
     *
     * @param AssetsHelper $assetHelper
     * @param ConfigProvider $configProvider
     */
    public function __construct(AssetsHelper $assetHelper, ConfigProvider $configProvider)
    {
        $this->assetHelper    = $assetHelper;
        $this->configProvider = $configProvider;
    }

    /**
     * @return array
     */
    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => ['loadAssets', -255],
        ];
    }

    /**
     * @param GetResponseEvent $event
     */
    public function loadAssets(GetResponseEvent $event): void
    {
        if ($this->configProvider->pluginIsEnabled() && $event->isMasterRequest()) {
            $this->assetHelper->addScript('plugins/CustomObjectsBundle/Assets/js/custom-objects.js');
            $this->assetHelper->addScript('plugins/CustomObjectsBundle/Assets/js/co-form.js');
            $this->assetHelper->addStylesheet('plugins/CustomObjectsBundle/Assets/css/custom-objects.css');
        }
    }
}
