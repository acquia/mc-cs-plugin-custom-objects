<?php

declare(strict_types=1);

namespace MauticPlugin\CustomObjectsBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Schema\SchemaException;
use Mautic\IntegrationsBundle\Migration\AbstractMigration;

class Version_0_0_17 extends AbstractMigration
{
    /** @var Schema */
    private $schema;

    protected function isApplicable(Schema $schema): bool
    {
        $this->schema = $schema;
        try {
            return !$this->schema->getTable($this->concatPrefix('custom_object'))->hasForeignKey('FK_CO_RELATIONSHIP_OBJECT');
        } catch (SchemaException $e) {
            return false;
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function up(): void
    {
        // Beta instances are corrupted with index & foreign key through non-migration routes. It has to be managed through migrations.
        if ($this->schema->getTable($this->concatPrefix('custom_object'))->hasForeignKey('FK_45528188D74EDE85')) {
            $this->addSql("ALTER TABLE {$this->concatPrefix('custom_object')} DROP FOREIGN KEY FK_45528188D74EDE85;");
        }

        if ($this->schema->getTable($this->concatPrefix('custom_object'))->hasIndex('UNIQ_45528188D74EDE85')) {
            $this->addSql("ALTER TABLE {$this->concatPrefix('custom_object')} DROP INDEX UNIQ_45528188D74EDE85;");
        }
        $this->addSql("ALTER TABLE {$this->concatPrefix('custom_object')} ADD CONSTRAINT FK_CO_RELATIONSHIP_OBJECT FOREIGN KEY (relationship_object) REFERENCES {$this->concatPrefix('custom_object')} (id) ON DELETE SET NULL;");
        $this->addSql("ALTER TABLE {$this->concatPrefix('custom_object')} ADD UNIQUE INDEX UNIQ_CO_RELATIONSHIP_OBJECT (relationship_object);");
    }
}
