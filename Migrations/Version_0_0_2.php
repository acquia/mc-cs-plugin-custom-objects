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
use Doctrine\DBAL\Schema\Schema;

class Version_0_0_2 extends AbstractMigration
{
    /**
     * {@inheritdoc}
     */
    protected function isApplicable(Schema $schema): bool
    {
        return !$schema->hasTable("{$this->tablePrefix}custom_item_xref_custom_item");
    }

    /**
     * {@inheritdoc}
     */
    protected function up(): void
    {
        $this->addSql("CREATE TABLE {$this->tablePrefix}custom_item_xref_custom_item (
                custom_item_id BIGINT UNSIGNED NOT NULL, 
                parent_custom_item_id BIGINT UNSIGNED NOT NULL, 
                date_added DATETIME NOT NULL COMMENT '(DC2Type:datetime)', 
                INDEX IDX_77DE7EF715363B93 (custom_item_id), 
                INDEX IDX_77DE7EF7BEB4F98 (parent_custom_item_id), 
                PRIMARY KEY(custom_item_id, parent_custom_item_id)
            ) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB
        ");

        $this->addSql("ALTER TABLE {$this->tablePrefix}custom_item_xref_custom_item 
            ADD CONSTRAINT FK_77DE7EF715363B93 
            FOREIGN KEY (custom_item_id) 
            REFERENCES {$this->tablePrefix}custom_item (id) ON DELETE CASCADE
        ");

        $this->addSql("ALTER TABLE {$this->tablePrefix}custom_item_xref_custom_item 
            ADD CONSTRAINT FK_77DE7EF7BEB4F98 
            FOREIGN KEY (parent_custom_item_id) 
            REFERENCES {$this->tablePrefix}custom_item (id) ON DELETE CASCADE
        ");
    }
}
