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

use Mautic\CoreBundle\Templating\Helper\AssetsHelper;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use MauticPlugin\CustomObjectsBundle\Provider\ConfigProvider;
use Symfony\Component\HttpFoundation\Request;

class AssetsSubscriber implements EventSubscriberInterface
{
    /**
     * @var AssetsHelper
     */
    private $assetHelper;

    /**
     * @var ConfigProvider
     */
    private $configProvider;

    public function __construct(
        AssetsHelper $assetHelper,
        ConfigProvider $configProvider
    ) {
        $this->assetHelper    = $assetHelper;
        $this->configProvider = $configProvider;
    }

    /**
     * @return mixed[]
     */
    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => ['loadAssets', -255],
        ];
    }

    public function loadAssets(GetResponseEvent $event): void
    {
        if ($this->configProvider->pluginIsEnabled() && $event->isMasterRequest() && $this->isMauticAdministrationPage($event->getRequest())) {
            $this->assetHelper->addScript('plugins/CustomObjectsBundle/Assets/js/custom-objects.js');
            $this->assetHelper->addScript('plugins/CustomObjectsBundle/Assets/js/co-form.js');
            $this->assetHelper->addStylesheet('plugins/CustomObjectsBundle/Assets/css/custom-objects.css');
        }
    }

    /**
     * Returns true for routes that starts with /s/.
     *
     * @param Request $request
     *
     * @return bool
     */
    private function isMauticAdministrationPage(Request $request): bool
    {
        return preg_match('/^\/s\//', $request->getPathInfo()) >= 1;
    }
}
