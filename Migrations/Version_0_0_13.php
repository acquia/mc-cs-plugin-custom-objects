<?php

declare(strict_types=1);

namespace MauticPlugin\CustomObjectsBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Schema\SchemaException;
use Mautic\IntegrationsBundle\Migration\AbstractMigration;

class Version_0_0_13 extends AbstractMigration
{
    /**
     * {@inheritdoc}
     */
    protected function isApplicable(Schema $schema): bool
    {
        try {
            return !$schema->getTable($this->concatPrefix('custom_object'))->hasColumn('relationship_object');
        } catch (SchemaException $e) {
            return false;
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function up(): void
    {
        $this->addSql("ALTER TABLE {$this->concatPrefix('custom_object')} ADD COLUMN `relationship_object` int(10) unsigned DEFAULT NULL AFTER `master_object`");
    }
}
