<?php

declare(strict_types=1);

namespace MauticPlugin\CustomObjectsBundle\Tests\Unit\EventListener;

use Mautic\PluginBundle\Entity\Plugin;
use Mautic\PluginBundle\Event\PluginInstallEvent;
use Mautic\PluginBundle\Event\PluginUpdateEvent;
use Mautic\PluginBundle\PluginEvents;
use MauticPlugin\CustomObjectsBundle\EventListener\PluginSchemaSubscriber;
use MauticPlugin\CustomObjectsBundle\Helper\PluginSchemaInstaller;
use PHPUnit\Framework\TestCase;

class PluginSchemaSubscriberTest extends TestCase
{
    public function testSubscribesToPluginInstallAndUpdate(): void
    {
        $events = PluginSchemaSubscriber::getSubscribedEvents();

        $this->assertArrayHasKey(PluginEvents::ON_PLUGIN_INSTALL, $events);
        $this->assertArrayHasKey(PluginEvents::ON_PLUGIN_UPDATE, $events);
    }

    public function testInstallCreatesTablesForCustomObjects(): void
    {
        $installer  = $this->createInstallerSpy();
        $subscriber = new PluginSchemaSubscriber($installer);

        $subscriber->onPluginInstall(new PluginInstallEvent($this->createPlugin('Custom Objects')));

        $this->assertSame(1, $installer->calls);
    }

    public function testInstallIgnoresOtherPlugins(): void
    {
        $installer  = $this->createInstallerSpy();
        $subscriber = new PluginSchemaSubscriber($installer);

        $subscriber->onPluginInstall(new PluginInstallEvent($this->createPlugin('Some Other Plugin')));

        $this->assertSame(0, $installer->calls);
    }

    public function testUpdateCreatesMissingTablesForCustomObjects(): void
    {
        $installer  = $this->createInstallerSpy();
        $subscriber = new PluginSchemaSubscriber($installer);

        $subscriber->onPluginUpdate(new PluginUpdateEvent($this->createPlugin('Custom Objects'), '1.0.0'));

        $this->assertSame(1, $installer->calls);
    }

    public function testUpdateIgnoresOtherPlugins(): void
    {
        $installer  = $this->createInstallerSpy();
        $subscriber = new PluginSchemaSubscriber($installer);

        $subscriber->onPluginUpdate(new PluginUpdateEvent($this->createPlugin('Some Other Plugin'), '1.0.0'));

        $this->assertSame(0, $installer->calls);
    }

    private function createPlugin(string $name): Plugin
    {
        $plugin = new Plugin();
        $plugin->setName($name);

        return $plugin;
    }

    /**
     * Counts calls without touching Doctrine. The schema work itself is covered by
     * PluginSchemaInstaller against a real database.
     */
    private function createInstallerSpy(): PluginSchemaInstaller
    {
        return new class extends PluginSchemaInstaller {
            public int $calls = 0;

            public function __construct()
            {
            }

            public function ensureTablesExist(): void
            {
                ++$this->calls;
            }
        };
    }
}
