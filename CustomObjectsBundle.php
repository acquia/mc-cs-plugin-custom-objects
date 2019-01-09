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
use Mautic\PluginBundle\Bundle\PluginBundleBase;
use Mautic\PluginBundle\Entity\Plugin;
use Mautic\CoreBundle\Factory\MauticFactory;
use Doctrine\DBAL\Connection;

class CustomObjectsBundle extends PluginBundleBase
{
    /**
     * @var string
     */
    private static $tableName = MAUTIC_TABLE_PREFIX.'custom_field_value_text';

    /**
     * @var string
     */
    private static $indexName = MAUTIC_TABLE_PREFIX.'value_index';

    /**
     * @param Plugin        $plugin
     * @param MauticFactory $factory
     * @param array|null    $metadata
     * @param Schema|bool|null   $installedSchema
     *
     * @throws \Exception
     */
    public static function onPluginInstall(Plugin $plugin, MauticFactory $factory, $metadata = null, $installedSchema = null): void
    {
        if ($installedSchema === true) {
            // Schema exists
            return;
        }

        parent::onPluginInstall($plugin, $factory, $metadata, $installedSchema);
        $queries[] = self::createIndexQuery();
        self::commit($factory->getDatabase(), $queries);
    }

    /**
    * @param Plugin        $plugin
    * @param MauticFactory $factory
    * @param array|null    $metadata
    * @param Schema|null   $installedSchema
    *
    * @throws \Exception
    */
    public static function onPluginUpdate(Plugin $plugin, MauticFactory $factory, $metadata = null, Schema $installedSchema = null): void
    {
        $queries = [self::createIndexQueryIfDoesNotExist($installedSchema)];
        self::commit($factory->getDatabase(), $queries);
    }

    /**
     * @param Schema $schema
     * 
     * @return string
     */
    private static function createIndexQueryIfDoesNotExist(Schema $schema): string
    {
        if (!$schema->hasTable(self::$tableName)) {
            return '';
        }
        
        if ($schema->getTable(self::$tableName)->hasIndex(self::$indexName)) {
            return '';
        }
        
        return self::createIndexQuery();
    }

    /**
     * @return string
     */
    private static function createIndexQuery(): string
    {
        return sprintf('CREATE INDEX %s ON %s (value(64))', self::$indexName, self::$tableName);
    }

    /**
     * @param Connection $connection
     * @param array $queries
     */
    private static function commit(Connection $connection, array $queries): void
    {
        if (!empty($queries)) {

            $connection->beginTransaction();
            try {
                foreach ($queries as $query) {
                    if (!$query) {
                        continue;
                    }
                    $connection->query($query);
                }

                $connection->commit();
            } catch (\Exception $e) {
                $connection->rollBack();

                throw $e;
            }
        }
    }
}
