<?php

declare(strict_types=1);

namespace MauticPlugin\CustomObjectsBundle;

use Doctrine\DBAL\Schema\Schema;
use Mautic\CoreBundle\Factory\MauticFactory;
use Mautic\IntegrationsBundle\Bundle\AbstractPluginBundle;
use MauticPlugin\CustomObjectsBundle\DependencyInjection\Compiler\CustomFieldTypePass;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class CustomObjectsBundle extends AbstractPluginBundle
{
    /**
     * {@inheritdoc}
     */
    public function build(ContainerBuilder $container): void
    {
        $container->addCompilerPass(new CustomFieldTypePass());
    }

    /**
     * In some rare cases it can happen that the plugin tables weren't created on plugin install.
     * Create them on plugin update if they are missing.
     */
    protected static function installAllTablesIfMissing(Schema $schema, string $tablePrefix, MauticFactory $factory, array $metadata = null): void
    {
        if (!$schema->hasTable($tablePrefix.'custom_object')) {
            self::installPluginSchema($metadata, $factory, null);
        }
    }
}
