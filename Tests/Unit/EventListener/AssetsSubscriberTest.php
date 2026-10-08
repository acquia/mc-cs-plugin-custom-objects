<?php

declare(strict_types=1);

namespace MauticPlugin\CustomObjectsBundle\Tests\Unit\EventListener;

use Mautic\CoreBundle\Twig\Helper\AssetsHelper;
use MauticPlugin\CustomObjectsBundle\EventListener\AssetsSubscriber;
use MauticPlugin\CustomObjectsBundle\Provider\ConfigProvider;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\RequestEvent;

class AssetsSubscriberTest extends \PHPUnit\Framework\TestCase
{
    private $assetsHelper;

    private $configProvider;

    private $getRequestEvent;

    private $request;

    private $assetsSubscriber;

    protected function setUp(): void
    {
        parent::setUp();

        $this->assetsHelper     = $this->createMock(AssetsHelper::class);
        $this->configProvider   = $this->createMock(ConfigProvider::class);
        $this->getRequestEvent = $this->createMock(RequestEvent::class);
        $this->request          = $this->createMock(Request::class);
        $this->assetsSubscriber = new AssetsSubscriber($this->assetsHelper, $this->configProvider);
    }

    public function testPluginDisabled(): void
    {
        $this->configProvider->expects($this->once())
            ->method('pluginIsEnabled')
            ->willReturn(false);

        $this->getRequestEvent->expects($this->never())
            ->method('isMainrequest');

        $this->assetsHelper->expects($this->never())
            ->method('addStylesheet');

        $this->assetsSubscriber->loadAssets($this->getRequestEvent);
    }

    public function testPluginEnabledOnPublicPage(): void
    {
        $this->configProvider->expects($this->once())
            ->method('pluginIsEnabled')
            ->willReturn(true);

        $this->getRequestEvent->expects($this->once())
            ->method('isMainrequest')
            ->willReturn(true);

        $this->getRequestEvent->expects($this->once())
            ->method('getRequest')
            ->willReturn($this->request);

        $this->request->expects($this->once())
            ->method('getPathInfo')
            ->willReturn('/email/unsubscribe/5c9f4105548a6783784018');

        $this->assetsHelper->expects($this->never())
            ->method('addStylesheet');

        $this->assetsSubscriber->loadAssets($this->getRequestEvent);
    }

    public function testPluginEnabled(): void
    {
        $this->configProvider->expects($this->once())
            ->method('pluginIsEnabled')
            ->willReturn(true);

        $this->getRequestEvent->expects($this->once())
            ->method('isMainrequest')
            ->willReturn(true);

        $this->getRequestEvent->expects($this->once())
            ->method('getRequest')
            ->willReturn($this->request);

        $this->request->expects($this->once())
            ->method('getPathInfo')
            ->willReturn('/s/dashboard');

        $this->assetsHelper->expects($this->once())
            ->method('addStylesheet');

        $this->assetsSubscriber->loadAssets($this->getRequestEvent);
    }
}
