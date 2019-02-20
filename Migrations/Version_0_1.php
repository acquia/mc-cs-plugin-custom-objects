<?php

/*
* @copyright   2019 Mautic, Inc. All rights reserved
* @author      Mautic, Inc.
*
* @link        https://mautic.com
*
* @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
*/

namespace MauticPlugin\CustomObjectsBundle\Migrations;

class Version_0_1 extends AbstractMigration
{
    public function up(): void
    {
        $this->addSql("ALTER TABLE `{$this->tablePrefix}custom_field` ADD `required` tinyint(1) unsigned DEFAULT 0");
        $this->addSql("ALTER TABLE `{$this->tablePrefix}custom_field` ADD `default_value` varchar(255) COLLATE utf8_unicode_ci NULL");
    }
}