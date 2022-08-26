<?php

declare(strict_types=1);

namespace MauticPlugin\CustomObjectsBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Mautic\CoreBundle\Exception\SchemaException;
use Mautic\IntegrationsBundle\Migration\AbstractMigration;

class Version_0_0_21 extends AbstractMigration
{
    private string $table = 'custom_field';

    protected function isApplicable(Schema $schema): bool
    {
        $tableCustomObject = $this->concatPrefix($this->table);

        try {
            return !$schema->getTable($tableCustomObject)->hasColumn('is_unique_identifier');
        } catch (SchemaException $e) {
            return false;
        }
    }

    public function up(): void
    {
        $tableCustomObject = $this->concatPrefix($this->table);

        $this->addSql("ALTER TABLE {$tableCustomObject} ADD is_unique_identifier TINYINT(1) DEFAULT '0' NOT NULL");
    }
}
