<?php

declare(strict_types=1);

namespace MauticPlugin\CustomObjectsBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Schema\SchemaException;
use Mautic\IntegrationsBundle\Migration\AbstractMigration;

class Version_0_0_18 extends AbstractMigration
{
    /** @var Schema */
    private $schema;

    protected function isApplicable(Schema $schema): bool
    {
        $this->schema = $schema;
        try {
            return !$this->schema->getTable($this->concatPrefix('custom_object'))->hasForeignKey('FK_CO_MASTER_OBJECT');
        } catch (SchemaException $e) {
            return false;
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function up(): void
    {
        if ($this->schema->getTable($this->concatPrefix('custom_object'))->hasForeignKey('FK_9C007FE8594D0CC2')) {
            $this->addSql("ALTER TABLE {$this->concatPrefix('custom_object')} DROP FOREIGN KEY FK_9C007FE8594D0CC2;");
        }

        // Dropping that index because we use UNIQ_CO_MASTER_OBJECT index instead.
        if ($this->schema->getTable($this->concatPrefix('custom_object'))->hasIndex('master_object')) {
            $this->addSql("ALTER TABLE {$this->concatPrefix('custom_object')} DROP INDEX master_object;");
        }

        // Fix column type. Sometimes it doesn't match what we have in loadMetadata method. Version_0_0_11 migration is not correct.
        $this->addSql("ALTER TABLE {$this->concatPrefix('custom_object')} CHANGE COLUMN `master_object` `master_object` INT(10) UNSIGNED NULL DEFAULT NULL;");

        if ($this->schema->getTable($this->concatPrefix('custom_object'))->hasIndex('UNIQ_9C007FE8594D0CC2')) {
            $this->addSql("ALTER TABLE {$this->concatPrefix('custom_object')} DROP INDEX UNIQ_9C007FE8594D0CC2;");
        }

        $this->addSql("ALTER TABLE {$this->concatPrefix('custom_object')} ADD CONSTRAINT FK_CO_MASTER_OBJECT FOREIGN KEY (master_object) REFERENCES {$this->concatPrefix('custom_object')} (id) ON DELETE CASCADE;");
        $this->addSql("ALTER TABLE {$this->concatPrefix('custom_object')} ADD UNIQUE INDEX UNIQ_CO_MASTER_OBJECT (master_object);");
    }
}
