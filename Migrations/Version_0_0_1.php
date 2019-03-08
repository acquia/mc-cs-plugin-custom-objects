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

use Doctrine\DBAL\Schema\MySqlSchemaManager;
use MauticPlugin\CustomObjectsBundle\Migration\AbstractMigration;

class Version_0_0_1 extends AbstractMigration
{
    public function isApplicable(): bool
    {
        /** @var MySqlSchemaManager $sm */
        $sm = $this->entityManager->getConnection()->getSchemaManager();

        if (!$sm->tablesExist(["{$this->tablePrefix}custom_object"])) {
            return false;
        }

        $columns = $sm->listTableColumns('custom_object');

        if (array_key_exists('description', $columns)) {
            return false;
        }

        return true;
    }

    public function up(): void
    {
        $this->addSql("
            ALTER TABLE `{$this->tablePrefix}custom_object`
            ADD `description` varchar(255) NULL AFTER `name_singular`
        ");
    }
}
