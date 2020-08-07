<?php

declare(strict_types=1);

namespace MauticPlugin\CustomObjectsBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Schema\SchemaException;
use Mautic\IntegrationsBundle\Migration\AbstractMigration;

class Version_0_0_16 extends AbstractMigration
{
    protected function isApplicable(Schema $schema): bool
    {
        try {
            return !$schema->getTable($this->concatPrefix('custom_object'))->hasForeignKey('FK_CO_RELATIONSHIP_OBJECT');
        } catch (SchemaException $e) {
            return false;
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function up(): void
    {
        $this->addSql("ALTER TABLE {$this->concatPrefix('custom_object')} ADD CONSTRAINT FK_CO_RELATIONSHIP_OBJECT FOREIGN KEY (relationship_object) REFERENCES {$this->concatPrefix('custom_object')} (id) ON DELETE SET NULL;");
        $this->addSql("ALTER TABLE {$this->concatPrefix('custom_object')} ADD UNIQUE INDEX UNIQ_CO_RELATIONSHIP_OBJECT (relationship_object);");
    }
}
