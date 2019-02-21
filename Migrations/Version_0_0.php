<?php

declare(strict_types=1);

/*
* @copyright   2019 Mautic, Inc. All rights reserved
* @author      Mautic, Inc.
*
* @link        https://mautic.com
*
* @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
*/

namespace MauticPlugin\CustomObjectsBundle\Migrations;

use MauticPlugin\CustomObjectsBundle\Migration\AbstractMigration;

class Version_0_0 extends AbstractMigration
{
    public function isApplicable(): bool
    {
        return !$this->entityManager->getConnection()->getSchemaManager()
            ->tablesExist(["{$this->tablePrefix}custom_object"]);
    }

    public function up(): void
    {
        $this->addSql("
            CREATE TABLE `{$this->tablePrefix}custom_object` (
                `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
                `is_published` tinyint(1) NOT NULL,
                `date_added` datetime DEFAULT NULL COMMENT '(DC2Type:datetime)',
                `created_by` int(11) DEFAULT NULL,
                `created_by_user` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
                `date_modified` datetime DEFAULT NULL COMMENT '(DC2Type:datetime)',
                `modified_by` int(11) DEFAULT NULL,
                `modified_by_user` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
                `checked_out` datetime DEFAULT NULL COMMENT '(DC2Type:datetime)',
                `checked_out_by` int(11) DEFAULT NULL,
                `checked_out_by_user` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
                `name_plural` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
                `name_singular` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
                `lang` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
                `category_id` int(10) unsigned DEFAULT NULL,
                PRIMARY KEY (`id`),
                KEY `IDX_9C007FE812469DE2` (`category_id`)
            ) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci
        ");

        $this->addSql("
            CREATE TABLE `{$this->tablePrefix}custom_field` (
                `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
                `custom_object_id` int(10) unsigned NOT NULL,
                `is_published` tinyint(1) NOT NULL,
                `date_added` datetime DEFAULT NULL COMMENT '(DC2Type:datetime)',
                `created_by` int(11) DEFAULT NULL,
                `created_by_user` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
                `date_modified` datetime DEFAULT NULL COMMENT '(DC2Type:datetime)',
                `modified_by` int(11) DEFAULT NULL,
                `modified_by_user` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
                `checked_out` datetime DEFAULT NULL COMMENT '(DC2Type:datetime)',
                `checked_out_by` int(11) DEFAULT NULL,
                `checked_out_by_user` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
                `label` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
                `type` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
                `field_order` int(10) unsigned DEFAULT NULL,
                `default_value` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
                `required` tinyint(1) unsigned DEFAULT 0,
                PRIMARY KEY (`id`),
                KEY `IDX_98F8BD316917218D` (`custom_object_id`),
                CONSTRAINT `FK_98F8BD316917218D` FOREIGN KEY (`custom_object_id`) REFERENCES `{$this->tablePrefix}custom_object` (`id`) ON DELETE CASCADE
            ) ENGINE=InnoDB AUTO_INCREMENT=72 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci        
        ");

        $this->addSql("
            CREATE TABLE `{$this->tablePrefix}custom_field_value_date` (
                `custom_field_id` int(10) unsigned NOT NULL,
                `custom_item_id` bigint(20) unsigned NOT NULL,
                `value` date DEFAULT NULL COMMENT '(DC2Type:date)',
                PRIMARY KEY (`custom_field_id`,`custom_item_id`),
                KEY `IDX_C29E8740A1E5E0D5` (`custom_field_id`),
                KEY `IDX_C29E874015363B94` (`custom_item_id`),
                KEY `value_index` (`value`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci
        ");

        $this->addSql("
            CREATE TABLE `{$this->tablePrefix}custom_field_value_datetime` (
                `custom_field_id` int(10) unsigned NOT NULL,
                `custom_item_id` bigint(20) unsigned NOT NULL,
                `value` datetime DEFAULT NULL COMMENT '(DC2Type:datetime)',
                PRIMARY KEY (`custom_field_id`,`custom_item_id`),
                KEY `IDX_C29E8740A1E5E0D4` (`custom_field_id`),
                KEY `IDX_C29E874015363B93` (`custom_item_id`),
                KEY `value_index` (`value`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci
        ");

        $this->addSql("
            CREATE TABLE `{$this->tablePrefix}custom_field_value_int` (
                `custom_field_id` int(10) unsigned NOT NULL,
                `custom_item_id` int(10) unsigned NOT NULL,
                `value` int(11) DEFAULT NULL,
                PRIMARY KEY (`custom_field_id`,`custom_item_id`),
                KEY `IDX_C48B0828A1E5E0D4` (`custom_field_id`),
                KEY `IDX_C48B082815363B93` (`custom_item_id`),
                KEY `value_index` (`value`),
                CONSTRAINT `FK_C48B082815363B93` FOREIGN KEY (`custom_item_id`) REFERENCES `{$this->tablePrefix}custom_item` (`id`) ON DELETE CASCADE,
                CONSTRAINT `FK_C48B0828A1E5E0D4` FOREIGN KEY (`custom_field_id`) REFERENCES `{$this->tablePrefix}custom_field` (`id`) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci
        ");

        $this->addSql("
            CREATE TABLE `{$this->tablePrefix}custom_field_value_text` (
                `custom_field_id` int(10) unsigned NOT NULL,
                `custom_item_id` int(10) unsigned NOT NULL,
                `value` longtext COLLATE utf8_unicode_ci DEFAULT NULL,
                PRIMARY KEY (`custom_field_id`,`custom_item_id`),
                KEY `IDX_B28856F5A1E5E0D4` (`custom_field_id`),
                KEY `IDX_B28856F515363B93` (`custom_item_id`),
                KEY `value_index` (`value`(64)),
                CONSTRAINT `FK_B28856F515363B93` FOREIGN KEY (`custom_item_id`) REFERENCES `{$this->tablePrefix}custom_item` (`id`) ON DELETE CASCADE,
                CONSTRAINT `FK_B28856F5A1E5E0D4` FOREIGN KEY (`custom_field_id`) REFERENCES `{$this->tablePrefix}custom_field` (`id`) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci
        ");

        $this->addSql("
            CREATE TABLE `{$this->tablePrefix}custom_item` (
                `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
                `custom_object_id` int(10) unsigned NOT NULL,
                `is_published` tinyint(1) NOT NULL,
                `date_added` datetime DEFAULT NULL COMMENT '(DC2Type:datetime)',
                `created_by` int(11) DEFAULT NULL,
                `created_by_user` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
                `date_modified` datetime DEFAULT NULL COMMENT '(DC2Type:datetime)',
                `modified_by` int(11) DEFAULT NULL,
                `modified_by_user` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
                `checked_out` datetime DEFAULT NULL COMMENT '(DC2Type:datetime)',
                `checked_out_by` int(11) DEFAULT NULL,
                `checked_out_by_user` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
                `name` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
                `lang` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
                `category_id` int(10) unsigned DEFAULT NULL,
                PRIMARY KEY (`id`),
                KEY `IDX_1E6DEDE36917218D` (`custom_object_id`),
                KEY `IDX_1E6DEDE312469DE2` (`category_id`),
                CONSTRAINT `FK_1E6DEDE36917218D` FOREIGN KEY (`custom_object_id`) REFERENCES `{$this->tablePrefix}custom_object` (`id`) ON DELETE CASCADE
            ) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci
        ");

        $this->addSql("
            CREATE TABLE `{$this->tablePrefix}custom_item_xref_company` (
                `custom_item_id` bigint(20) unsigned NOT NULL,
                `company_id` int(11) NOT NULL,
                `date_added` datetime NOT NULL COMMENT '(DC2Type:datetime)',
                PRIMARY KEY (`custom_item_id`,`company_id`),
                KEY `IDX_84AA435A15363B93` (`custom_item_id`),
                KEY `IDX_84AA435A979B1AD6` (`company_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci
        ");

        $this->addSql("
            CREATE TABLE `{$this->tablePrefix}custom_item_xref_contact` (
                `custom_item_id` int(10) unsigned NOT NULL,
                `contact_id` bigint(20) unsigned NOT NULL,
                `date_added` datetime NOT NULL COMMENT '(DC2Type:datetime)',
                PRIMARY KEY (`custom_item_id`,`contact_id`),
                KEY `IDX_8777AC2D15363B93` (`custom_item_id`),
                KEY `IDX_8777AC2DE7A1254A` (`contact_id`),
                CONSTRAINT `FK_8777AC2D15363B93` FOREIGN KEY (`custom_item_id`) REFERENCES `{$this->tablePrefix}custom_item` (`id`) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci
        ");
    }
}