<?php

declare(strict_types=1);

namespace MauticPlugin\CustomObjectsBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Mautic\IntegrationsBundle\Migration\AbstractMigration;

class Version_0_0_3 extends AbstractMigration
{
    /**
     * @var string
     */
    private $table = 'custom_field_option';

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
                `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
                `custom_field_id` int(10) unsigned NOT NULL,
                `label` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
                `value` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
                PRIMARY KEY (`id`)
            ) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB
        ");

        $this->addSql($this->generateAlterTableForeignKeyStatement(
            $this->table,
            ['custom_field_id'],
            'custom_field',
            ['id'],
            'ON DELETE CASCADE'
        ));
    }
}
