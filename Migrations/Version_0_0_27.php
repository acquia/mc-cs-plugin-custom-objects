<?php

declare(strict_types=1);

namespace MauticPlugin\CustomObjectsBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Schema\SchemaException;
use Mautic\IntegrationsBundle\Migration\AbstractMigration;

class Version_0_0_27 extends AbstractMigration
{
    private string $table = 'custom_item';

    protected function isApplicable(Schema $schema): bool
    {
        $tableCustomItem = $this->concatPrefix($this->table);

        try {
            return !$schema->getTable($tableCustomItem)->hasColumn('unique_hash');
        } catch (SchemaException $e) {
            return false;
        }
    }

    public function up(): void
    {
        $tableCustomItem = $this->concatPrefix($this->table);
        $indexName       = $this->generatePropertyName($this->table, 'UNIQ', ['unique_hash']);
        $this->addSql("ALTER TABLE {$tableCustomItem} ADD unique_hash VARCHAR(191), ADD UNIQUE KEY {$indexName} (unique_hash)");
    }
}
