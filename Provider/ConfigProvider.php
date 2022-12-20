<?php

declare(strict_types=1);

namespace MauticPlugin\CustomObjectsBundle\Provider;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use MauticPlugin\CustomObjectsBundle\Entity\CustomObject;

class ConfigProvider
{
    /**
     * @var string
     */
    public const CONFIG_PLUGIN_NAME                                = 'CustomObjectsBundle';
    public const CONFIG_PARAM_ENABLED                              = 'custom_objects_enabled';
    public const CONFIG_PARAM_ITEM_VALUE_TO_CONTACT_RELATION_LIMIT = 'custom_object_item_value_to_contact_relation_limit';

    /**
     * @var CoreParametersHelper
     */
    private $coreParametersHelper;

    /**
     * @var Connection
     */
    private $connection;

    public function __construct(CoreParametersHelper $coreParametersHelper, Connection $connection)
    {
        $this->coreParametersHelper = $coreParametersHelper;
        $this->connection           = $connection;
    }

    /**
     * Returns true if the Custom Objects plugin is enabled.
     */
    public function pluginIsEnabled(): bool
    {
        $pluginEnabled = (bool) $this->coreParametersHelper->get(self::CONFIG_PARAM_ENABLED, true);
        if (!$pluginEnabled) {
            return false;
        }

        try {
            $pluginWasInstalledBefore = $this->connection
                ->executeQuery('SELECT id FROM plugins WHERE bundle=:pluginName', ['pluginName' => self::CONFIG_PLUGIN_NAME])
                ->rowCount();
            $customObjectsTableExists = $this->connection
                ->executeQuery('SHOW TABLES LIKE :tableName', ['tableName' => CustomObject::TABLE_NAME])
                ->rowCount();
            return $pluginWasInstalledBefore && $customObjectsTableExists;
        } catch (Exception $e) {
            return false;
        }
    }
}
