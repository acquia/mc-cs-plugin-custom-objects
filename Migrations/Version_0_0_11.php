<?php

namespace MauticPlugin\CustomObjectsBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Mautic\CoreBundle\Exception\SchemaException;
use Mautic\IntegrationsBundle\Migration\AbstractMigration;

class Version_0_0_11 extends AbstractMigration
{
    /**
     * @var string
     */
    private $table = 'custom_object';

    /**
     * {@inheritdoc}
     */
    protected function isApplicable(Schema $schema): bool
    {
        try {
            return !$schema->getTable($this->concatPrefix($this->tableCustomObject))->hasColumn('type') ||
                !$schema->getTable($this->concatPrefix($this->tableCustomObject))->hasColumn('relationship') ||
                !$schema->getTable($this->concatPrefix($this->tableCustomObject))->hasColumn('master_object');
        } catch (SchemaException $e) {
            return false;
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function up(): void
    {
        $tableCustomObject = $this->concatPrefix($this->table);

        $this->addSql("ALTER TABLE {$tableCustomObject} ADD type INT, ADD INDEX (type)");

        $this->addSql("ALTER TABLE {$tableCustomObject} ADD relationship INT, ADD INDEX (relationship)");

        $this->addSql("ALTER TABLE {$tableCustomObject} ADD master_object INT, ADD INDEX (master_object)");
    }
}