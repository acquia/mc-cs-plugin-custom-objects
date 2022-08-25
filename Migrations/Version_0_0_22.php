<?php

declare(strict_types=1);

namespace MauticPlugin\CustomObjectsBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Mautic\CoreBundle\Exception\SchemaException;
use Mautic\IntegrationsBundle\Migration\AbstractMigration;

class Version_0_0_22 extends AbstractMigration
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

        $this->addSql("ALTER TABLE {$tableCustomItem} ADD unique_hash varchar(255) unique NULL ");
    }
}
