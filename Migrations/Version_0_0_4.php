<?php

declare(strict_types=1);

namespace MauticPlugin\CustomObjectsBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Mautic\IntegrationsBundle\Migration\AbstractMigration;

class Version_0_0_4 extends AbstractMigration
{
    /**
     * @var string
     */
    private $table = 'custom_field_value_option';

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
                custom_field_id INT UNSIGNED NOT NULL,
                custom_item_id BIGINT UNSIGNED NOT NULL,
                option_id INT UNSIGNED NOT NULL,
                {$this->generateIndexStatement($this->table, ['custom_field_id'])},
                {$this->generateIndexStatement($this->table, ['custom_item_id'])},
                {$this->generateIndexStatement($this->table, ['option_id'])},
                PRIMARY KEY(custom_field_id, custom_item_id, option_id)
            ) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB
        ");

        $this->addSql($this->generateAlterTableForeignKeyStatement(
            $this->table,
            ['custom_field_id'],
            'custom_field',
            ['id'],
            'ON DELETE CASCADE'
        ));

        $this->addSql($this->generateAlterTableForeignKeyStatement(
            $this->table,
            ['custom_item_id'],
            'custom_item',
            ['id'],
            'ON DELETE CASCADE'
        ));

        $this->addSql($this->generateAlterTableForeignKeyStatement(
            $this->table,
            ['option_id'],
            'custom_field_option',
            ['id'],
            'ON DELETE CASCADE'
        ));
    }
}
