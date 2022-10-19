<?php

declare(strict_types=1);

namespace MauticPlugin\CustomObjectsBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Mautic\IntegrationsBundle\Migration\AbstractMigration;

class Version_0_0_12 extends AbstractMigration
{
    /**
     * @var array<string>
     */
    private array $sql = [];

    /**
     * {@inheritdoc}
     */
    protected function isApplicable(Schema $schema): bool
    {
        if (!$schema->getTable($this->concatPrefix('custom_field_value_text'))->hasIndex('value_fulltext')) {
            $this->sql[] = "CREATE FULLTEXT INDEX name_fulltext ON {$this->concatPrefix('custom_item')} (name)";
        }
        if (!$schema->getTable($this->concatPrefix('custom_field_value_text'))->hasIndex('value_fulltext')) {
            $this->sql[] = "CREATE FULLTEXT INDEX value_fulltext ON {$this->concatPrefix('custom_field_value_text')} (value)";
        }

        $parts = [];
        if (!$schema->getTable($this->concatPrefix('custom_field_value_option'))->hasIndex('value_fulltext')) {
            $parts[] = 'DROP INDEX value_fulltext';
        }
        if (!$schema->getTable($this->concatPrefix('custom_field_value_option'))->hasIndex('unique')) {
            $parts[] = 'DROP INDEX `unique`';
        }

        if (!$schema->getTable($this->concatPrefix('custom_field_value_option'))->hasIndex('value_fulltext')) {
            $parts[] = "ADD FULLTEXT INDEX {$this->tablePrefix}value_fulltext (value)";
        }
        if (!$schema->getTable($this->concatPrefix('custom_field_value_option'))->hasColumn('id')) {
            if ($schema->getTable($this->concatPrefix('custom_field_value_option'))->hasPrimaryKey()) {
                $parts[] = 'DROP PRIMARY KEY';
            }
            $parts[] =  'ADD id BIGINT UNSIGNED AUTO_INCREMENT NOT NULL, ADD PRIMARY KEY (id)';
        }
        if (!$schema->getTable($this->concatPrefix('custom_field_value_option'))->hasIndex('unique')) {
            $parts[] =  "ADD UNIQUE INDEX `{$this->tablePrefix}unique` (value, custom_field_id, custom_item_id)";
        }

        if ($parts) {
            $this->sql[] = sprintf('ALTER TABLE %s %s', $this->concatPrefix('custom_field_value_option'), implode(','.PHP_EOL, $parts));
        }

        return (bool) count($this->sql);
    }

    /**
     * {@inheritdoc}
     */
    protected function up(): void
    {
        foreach ($this->sql as $sql) {
            $this->addSql($sql);
        }
    }
}
