<?php

declare(strict_types=1);

namespace MauticPlugin\CustomObjectsBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Schema\SchemaException;
use Mautic\IntegrationsBundle\Migration\AbstractMigration;

class Version_0_0_12 extends AbstractMigration
{
    /** @var Schema */
    private $schema;
    /**
     * {@inheritdoc}
     */
    protected function isApplicable(Schema $schema): bool
    {
        $this->schema = $schema;
        try {
            return !$schema->getTable($this->concatPrefix('custom_item'))->hasIndex('name_fulltext');
        } catch (SchemaException $e) {
            return false;
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function up(): void
    {
        $this->addSql("CREATE FULLTEXT INDEX name_fulltext ON {$this->concatPrefix('custom_item')} (name)");
        if (!$this->schema->getTable($this->concatPrefix('custom_field_value_text'))->hasIndex('value_fulltext')) {
            $this->addSql("CREATE FULLTEXT INDEX value_fulltext ON {$this->concatPrefix('custom_field_value_text')} (value)");
        }
        if (!$this->schema->getTable($this->concatPrefix('custom_field_value_option'))->hasIndex('value_fulltext')) {
            $this->addSql("CREATE FULLTEXT INDEX value_fulltext ON {$this->concatPrefix('custom_field_value_option')} (value)");
        }
        if (!$this->schema->getTable($this->concatPrefix('custom_field_value_option'))->hasColumn('id')) {
            $this->addSql("ALTER TABLE {$this->concatPrefix('custom_field_value_option')} ADD id BIGINT UNSIGNED AUTO_INCREMENT NOT NULL, DROP PRIMARY KEY, ADD PRIMARY KEY (id)");
        }
        if (!$this->schema->getTable($this->concatPrefix('custom_field_value_option'))->hasIndex('unique')) {
            $this->addSql("CREATE UNIQUE INDEX `unique` ON {$this->concatPrefix('custom_field_value_option')} (value, custom_field_id, custom_item_id)");
        }
    }
}
