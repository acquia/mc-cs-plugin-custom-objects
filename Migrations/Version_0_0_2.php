<?php

declare(strict_types=1);

namespace MauticPlugin\CustomObjectsBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Mautic\IntegrationsBundle\Migration\AbstractMigration;

class Version_0_0_2 extends AbstractMigration
{
    /**
     * @var string
     */
    private $table = 'custom_item_xref_custom_item';

    /**
     * {@inheritdoc}
     */
    protected function isApplicable(Schema $schema): bool
    {
        return !$schema->hasTable($this->concatPrefix($this->table));
    }

    /**
     * {@inheritdoc}
     */
    protected function up(): void
    {
        $this->addSql("CREATE TABLE {$this->concatPrefix($this->table)} (
                custom_item_id BIGINT UNSIGNED NOT NULL, 
                parent_custom_item_id BIGINT UNSIGNED NOT NULL, 
                date_added DATETIME NOT NULL COMMENT '(DC2Type:datetime)', 
                {$this->generateIndexStatement($this->table, ['custom_item_id'])},
                {$this->generateIndexStatement($this->table, ['parent_custom_item_id'])},
                PRIMARY KEY(custom_item_id, parent_custom_item_id)
            ) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB
        ");

        $this->addSql($this->generateAlterTableForeignKeyStatement(
            $this->table,
            ['custom_item_id'],
            'custom_item',
            ['id'],
            'ON DELETE CASCADE'
        ));

        $this->addSql($this->generateAlterTableForeignKeyStatement(
            $this->table,
            ['parent_custom_item_id'],
            'custom_item',
            ['id'],
            'ON DELETE CASCADE'
        ));
    }
}
