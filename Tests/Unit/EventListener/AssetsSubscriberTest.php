<?php

declare(strict_types=1);

/*
 * @copyright   2019 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\CustomObjectsBundle\Tests\Unit\EventListener;

use Mautic\CoreBundle\Templating\Helper\AssetsHelper;
use MauticPlugin\CustomObjectsBundle\Provider\ConfigProvider;
use MauticPlugin\CustomObjectsBundle\EventListener\AssetsSubscriber;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;

class AssetsSubscriberTest extends \PHPUnit_Framework_TestCase
{
    private $assetsHelper;

    private $configProvider;

    private $getResponseEvent;

    private $assetsSubscriber;

    protected function setUp(): void
    {
        parent::setUp();

        $this->assetsHelper     = $this->createMock(AssetsHelper::class);
        $this->configProvider   = $this->createMock(ConfigProvider::class);
        $this->getResponseEvent = $this->createMock(GetResponseEvent::class);
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

    public function testPluginEnabled(): void
    {
        $this->configProvider->expects($this->once())
            ->method('pluginIsEnabled')
            ->willReturn(true);

        $this->getResponseEvent->expects($this->once())
            ->method('isMasterRequest')
            ->willReturn(true);

        $this->assetsHelper->expects($this->once())
            ->method('addStylesheet');

        $this->assetsSubscriber->loadAssets($this->getResponseEvent);
    }
}
