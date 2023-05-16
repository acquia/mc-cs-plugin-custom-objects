<?php

declare(strict_types=1);

namespace MauticPlugin\CustomObjectsBundle\EventListener;

use Mautic\CoreBundle\Twig\Helper\AssetsHelper;
use MauticPlugin\CustomObjectsBundle\Provider\ConfigProvider;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

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
     */
    private function isMauticAdministrationPage(Request $request): bool
    {
        return preg_match('/^\/s\//', $request->getPathInfo()) >= 1;
    }
}
