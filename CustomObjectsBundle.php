<?php

declare(strict_types=1);

/*
 * @copyright   2018 Mautic, Inc. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.com
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\CustomObjectsBundle;

use Doctrine\DBAL\Schema\Schema;
use Mautic\CoreBundle\Factory\MauticFactory;
use MauticPlugin\CustomObjectsBundle\DependencyInjection\Compiler\CustomFieldTypePass;
use MauticPlugin\IntegrationsBundle\Bundle\AbstractPluginBundle;
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
     *
     * @param Schema        $schema
     * @param string        $tablePrefix
     * @param array|null    $metadata
     * @param MauticFactory $factory
     */
    protected static function installAllTablesIfMissing(Schema $schema, string $tablePrefix, MauticFactory $factory, array $metadata = null): void
    {
        if (!$schema->hasTable($tablePrefix.'custom_object')) {
            self::installPluginSchema($metadata, $factory, null);
        }
    }
}
