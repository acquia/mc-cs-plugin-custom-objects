<?php

declare(strict_types=1);

namespace MauticPlugin\CustomObjectsBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Schema\SchemaException;
use Mautic\IntegrationsBundle\Migration\AbstractMigration;

class Version_0_0_6 extends AbstractMigration
{
    /**
     * @var string
     */
    private $table = 'custom_field_option';

    /**
     * {@inheritdoc}
     */
    protected function isApplicable(Schema $schema): bool
    {
        try {
            return $schema->getTable($this->concatPrefix($this->table))->hasColumn('id');
        } catch (SchemaException $e) {
            return false;
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function up(): void
    {
        $this->addSql("ALTER TABLE {$this->concatPrefix($this->table)} MODIFY id INT UNSIGNED NOT NULL");
        $this->addSql("ALTER TABLE {$this->concatPrefix($this->table)} DROP PRIMARY KEY");
        $this->addSql("ALTER TABLE {$this->concatPrefix($this->table)} DROP id");
        $this->addSql("ALTER TABLE {$this->concatPrefix($this->table)} ADD PRIMARY KEY (custom_field_id, value)");
    }
}
