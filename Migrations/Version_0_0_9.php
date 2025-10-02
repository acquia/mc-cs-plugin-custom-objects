<?php

declare(strict_types=1);

namespace MauticPlugin\CustomObjectsBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Mautic\CoreBundle\Exception\SchemaException;
use Mautic\IntegrationsBundle\Migration\AbstractMigration;

class Version_0_0_9 extends AbstractMigration
{
    private string $tableCustomObject = 'custom_object';

    private string $tableCustomField = 'custom_field';

    /**
     * {@inheritdoc}
     */
    protected function isApplicable(Schema $schema): bool
    {
        try {
            return !$schema->getTable($this->concatPrefix($this->tableCustomObject))->hasColumn('alias');
        } catch (SchemaException) {
            return false;
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function up(): void
    {
        $tableCustomObject = $this->concatPrefix($this->tableCustomObject);
        $this->addSql("ALTER TABLE {$tableCustomObject} ADD alias VARCHAR(255) NOT NULL, ADD INDEX (alias)");

        $tableCustomField = $this->concatPrefix($this->tableCustomField);
        $this->addSql("ALTER TABLE {$tableCustomField} ADD alias VARCHAR(255) NOT NULL, ADD INDEX (alias)");
    }
}
