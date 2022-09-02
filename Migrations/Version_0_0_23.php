<?php

declare(strict_types=1);

namespace MauticPlugin\CustomObjectsBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Mautic\IntegrationsBundle\Migration\AbstractMigration;

class Version_0_0_23 extends AbstractMigration
{
    private string $table = 'custom_item_export_scheduler';

    /**
     * {@inheritdoc}
     */
    protected function isApplicable(Schema $schema): bool
    {
        $table = $schema->getTable($this->concatPrefix($this->table));

        return !$table->hasColumn('scheduled_datetime');
    }

    /**
     * {@inheritdoc}
     */
    protected function up(): void
    {
        $customItemExportSchedulerTableName = $this->concatPrefix($this->table);

        $this->addSql('ALTER TABLE '.$customItemExportSchedulerTableName." ADD scheduled_datetime DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)'");
    }
}
