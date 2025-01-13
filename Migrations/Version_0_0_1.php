<?php

declare(strict_types=1);

namespace MauticPlugin\CustomObjectsBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Schema\SchemaException;
use Mautic\IntegrationsBundle\Migration\AbstractMigration;

class Version_0_0_1 extends AbstractMigration
{
    private string $table = 'custom_object';

    /**
     * {@inheritdoc}
     */
    protected function isApplicable(Schema $schema): bool
    {
        try {
            return !$schema->getTable($this->concatPrefix($this->table))->hasColumn('description');
        } catch (SchemaException) {
            return false;
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function up(): void
    {
        $this->addSql("
            ALTER TABLE `{$this->concatPrefix($this->table)}`
            ADD `description` varchar(255) NULL AFTER `name_singular`
        ");
    }
}
