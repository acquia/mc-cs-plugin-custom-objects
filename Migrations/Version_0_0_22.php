<?php

declare(strict_types=1);

namespace MauticPlugin\CustomObjectsBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Mautic\IntegrationsBundle\Migration\AbstractMigration;

class Version_0_0_22 extends AbstractMigration
{
    /**
     * @var string
     */
    private $table = 'custom_item_export_scheduler';

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
        $customItemExportSchedulerTableName = $this->concatPrefix($this->table);
        $userIdColumnType =  $this->getUserIdColumnType();

        $this->addSql(
            "# Creating Table {$customItemExportSchedulerTableName}
            # -------------------------------------------------------------
            CREATE TABLE {$customItemExportSchedulerTableName} (
                id int(10) unsigned NOT NULL AUTO_INCREMENT,
                custom_object_id int(10) unsigned NOT NULL,
                user_id INT {$userIdColumnType} NOT NULL,
                PRIMARY KEY (id)
            ) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB
        ");
    }

    /**
     * @return string
     * @throws \Doctrine\DBAL\Schema\SchemaException
     */
    private function getUserIdColumnType(): string
    {
        $schema = new Schema();
        $usersTable = $schema->getTable($this->concatPrefix('users'));
        $column = $usersTable->getColumn('id');

        return $column->getUnsigned() ? 'UNSIGNED' : 'SIGNED';
    }
}
