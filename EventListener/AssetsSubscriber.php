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
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use MauticPlugin\CustomObjectsBundle\CustomObjectsBundle;

class AssetsSubscriber extends CommonSubscriber
{
    /**
     * @var AssetsHelper
     */
    private $assetHelper;

    /**
     * @var CoreParametersHelper
     */
    private $coreParametersHelper;

    /**
     *
     * @param AssetsHelper $assetHelper
     * @param CoreParametersHelper $coreParametersHelper
     */
    public function __construct(AssetsHelper $assetHelper, CoreParametersHelper $coreParametersHelper)
    {
        $this->assetHelper          = $assetHelper;
        $this->coreParametersHelper = $coreParametersHelper;
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
        $isEnabled = $this->coreParametersHelper->getParameter(CustomObjectsBundle::CONFIG_PARAM_ENABLED);
        if ($isEnabled && $event->isMasterRequest()) {
            $this->assetHelper->addScript('plugins/CustomObjectsBundle/Assets/js/custom-objects.js');
            $this->assetHelper->addScript('plugins/CustomObjectsBundle/Assets/js/co-form.js');
            $this->assetHelper->addStylesheet('plugins/CustomObjectsBundle/Assets/css/custom-objects.css');
        }
    }
}
