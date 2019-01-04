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
     * @param Plugin        $plugin
     * @param MauticFactory $factory
     * @param array|null    $metadata
     * @param Schema|null   $installedSchema
     *
     * @throws \Exception
     */
    public static function onPluginInstall(Plugin $plugin, MauticFactory $factory, $metadata = null, $installedSchema = null)
    {
        parent::onPluginInstall($plugin, $factory, $metadata, $installedSchema);
        $queries = [self::createIndexQueryIfDoesNotExist($installedSchema)];
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
    static public function onPluginUpdate(Plugin $plugin, MauticFactory $factory, $metadata = null, Schema $installedSchema = null)
    {
        $queries = [self::createIndexQueryIfDoesNotExist($installedSchema)];
        self::commit($factory->getDatabase(), $queries);
    }

    /**
     * @param Schema $schema
     * 
     * @return string
     */
    static private function createIndexQueryIfDoesNotExist(Schema $schema): string
    {
        $tableName = MAUTIC_TABLE_PREFIX.'custom_field_value_text';
        $indexName = MAUTIC_TABLE_PREFIX.'value_index';

        if (!$schema->hasTable($tableName)) {
            return '';
        }
        
        if ($schema->getTable($tableName)->hasIndex($indexName)) {
            return '';
        }
        
        return "CREATE INDEX {$indexName} ON {$tableName} (value(64))";
    }

    /**
     * @param Connection $connection
     * @param array $queries
     */
    static private function commit(Connection $connection, array $queries): void
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
                $connection->rollback();

                throw $e;
            }
        }
    }
}
