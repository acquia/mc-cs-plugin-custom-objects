<?php

declare(strict_types=1);

namespace MauticPlugin\CustomObjectsBundle\EventListener;

use Mautic\PluginBundle\Event\PluginInstallEvent;
use Mautic\PluginBundle\Event\PluginUpdateEvent;
use Mautic\PluginBundle\PluginEvents;
use MauticPlugin\CustomObjectsBundle\Helper\PluginSchemaInstaller;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Keeps the plugin's tables in place via the supported event API.
 *
 * Mautic still calls PluginBundleBase::onPluginInstall(), but it is marked
 * "@deprecated To be removed in 5.0. Listen to PluginEvents::ON_PLUGIN_INSTALL instead",
 * so schema creation cannot keep relying on it. AbstractPluginBundle additionally
 * overrides onPluginUpdate() to run the migration engine, which means the old
 * create-missing-tables safety net no longer runs at all.
 */
class PluginSchemaSubscriber implements EventSubscriberInterface
{
    private const PLUGIN_NAME = 'Custom Objects';

    public function __construct(private PluginSchemaInstaller $schemaInstaller)
    {
    }

    /**
     * @return array<string, string>
     */
    public static function getSubscribedEvents(): array
    {
        return [
            PluginEvents::ON_PLUGIN_INSTALL => 'onPluginInstall',
            PluginEvents::ON_PLUGIN_UPDATE  => 'onPluginUpdate',
        ];
    }

    public function onPluginInstall(PluginInstallEvent $event): void
    {
        if (!$event->checkContext(self::PLUGIN_NAME)) {
            return;
        }

        $this->schemaInstaller->ensureTablesExist();
    }

    /**
     * In some rare cases the plugin tables are not created on install. Create the
     * missing ones on update so the plugin can recover without a reinstall.
     */
    public function onPluginUpdate(PluginUpdateEvent $event): void
    {
        if (!$event->checkContext(self::PLUGIN_NAME)) {
            return;
        }

        $this->schemaInstaller->ensureTablesExist();
    }
}
