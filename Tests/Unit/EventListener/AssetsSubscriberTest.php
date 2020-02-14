<?php

declare(strict_types=1);

/*
 * @copyright   2019 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc
 *
 * @link        https://mautic.com
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\CustomObjectsBundle\Tests\Unit\EventListener;

use Mautic\CoreBundle\Templating\Helper\AssetsHelper;
use MauticPlugin\CustomObjectsBundle\EventListener\AssetsSubscriber;
use MauticPlugin\CustomObjectsBundle\Provider\ConfigProvider;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;

class AssetsSubscriberTest extends \PHPUnit\Framework\TestCase
{
    private $assetsHelper;

    private $configProvider;

    private $getResponseEvent;

    private $request;

    private $assetsSubscriber;

    protected function setUp(): void
    {
        parent::setUp();

        $this->assetsHelper     = $this->createMock(AssetsHelper::class);
        $this->configProvider   = $this->createMock(ConfigProvider::class);
        $this->getResponseEvent = $this->createMock(GetResponseEvent::class);
        $this->request          = $this->createMock(Request::class);
        $this->assetsSubscriber = new AssetsSubscriber($this->assetsHelper, $this->configProvider);
    }

    public function testPluginDisabled(): void
    {
        $this->configProvider->expects($this->once())
            ->method('pluginIsEnabled')
            ->willReturn(false);

        $this->getResponseEvent->expects($this->never())
            ->method('isMasterRequest');

        $this->assetsHelper->expects($this->never())
            ->method('addStylesheet');

        $this->assetsSubscriber->loadAssets($this->getResponseEvent);
    }

    public function testPluginEnabledOnPublicPage(): void
    {
        $this->configProvider->expects($this->once())
            ->method('pluginIsEnabled')
            ->willReturn(true);

        $this->getResponseEvent->expects($this->once())
            ->method('isMasterRequest')
            ->willReturn(true);

        $this->getResponseEvent->expects($this->once())
            ->method('getRequest')
            ->willReturn($this->request);

        $this->request->expects($this->once())
            ->method('getPathInfo')
            ->willReturn('/email/unsubscribe/5c9f4105548a6783784018');

        $this->assetsHelper->expects($this->never())
            ->method('addStylesheet');

        $this->assetsSubscriber->loadAssets($this->getResponseEvent);
    }

    public function testPluginEnabled(): void
    {
        $this->configProvider->expects($this->once())
            ->method('pluginIsEnabled')
            ->willReturn(true);

        $this->getResponseEvent->expects($this->once())
            ->method('isMasterRequest')
            ->willReturn(true);

        $this->getResponseEvent->expects($this->once())
            ->method('getRequest')
            ->willReturn($this->request);

        $this->request->expects($this->once())
            ->method('getPathInfo')
            ->willReturn('/s/dashboard');

        $this->assetsHelper->expects($this->once())
            ->method('addStylesheet');

        $this->assetsSubscriber->loadAssets($this->getResponseEvent);
    }
}
