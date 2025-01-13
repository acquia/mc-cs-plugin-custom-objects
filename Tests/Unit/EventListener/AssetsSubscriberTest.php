<?php

declare(strict_types=1);

namespace MauticPlugin\CustomObjectsBundle\Tests\Unit\EventListener;

use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\CoreBundle\Twig\Helper\AssetsHelper;
use MauticPlugin\CustomObjectsBundle\EventListener\AssetsSubscriber;
use MauticPlugin\CustomObjectsBundle\Provider\ConfigProvider;
use Symfony\Component\Asset\Packages;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\RequestEvent;

class AssetsSubscriberTest extends \PHPUnit\Framework\TestCase
{
    private $packages;

    private $coreParametersHelper;
    private $assetsHelper;

    private $configProvider;

    private $requestEvent;

    private $request;

    private $assetsSubscriber;

    protected function setUp(): void
    {
        parent::setUp();

        $this->packages             = $this->createMock(Packages::class);
        $this->coreParametersHelper = $this->createMock(CoreParametersHelper::class);
        $this->assetsHelper         = new AssetsHelper(
            $this->packages,
            $this->coreParametersHelper
        );
        $this->configProvider       = $this->createMock(ConfigProvider::class);
        $this->requestEvent         = $this->createMock(RequestEvent::class);
        $this->request              = $this->createMock(Request::class);
        $this->assetsSubscriber     = new AssetsSubscriber($this->assetsHelper, $this->configProvider);
    }

    public function testPluginDisabled(): void
    {
        $this->configProvider->expects($this->once())
            ->method('pluginIsEnabled')
            ->willReturn(false);

        $this->requestEvent->expects($this->never())
            ->method('isMainRequest');

        $this->assetsSubscriber->loadAssets($this->requestEvent);
    }

    public function testPluginEnabledOnPublicPage(): void
    {
        $this->configProvider->expects($this->once())
            ->method('pluginIsEnabled')
            ->willReturn(true);

        $this->requestEvent->expects($this->once())
            ->method('isMainRequest')
            ->willReturn(true);

        $this->requestEvent->expects($this->once())
            ->method('getRequest')
            ->willReturn($this->request);

        $this->request->expects($this->once())
            ->method('getPathInfo')
            ->willReturn('/email/unsubscribe/5c9f4105548a6783784018');

        $this->assetsSubscriber->loadAssets($this->requestEvent);
    }

    public function testPluginEnabled(): void
    {
        $this->configProvider->expects($this->once())
            ->method('pluginIsEnabled')
            ->willReturn(true);

        $this->requestEvent->expects($this->once())
            ->method('isMainRequest')
            ->willReturn(true);

        $this->requestEvent->expects($this->once())
            ->method('getRequest')
            ->willReturn($this->request);

        $this->request->expects($this->once())
            ->method('getPathInfo')
            ->willReturn('/s/dashboard');

        $this->assetsSubscriber->loadAssets($this->requestEvent);
    }
}
