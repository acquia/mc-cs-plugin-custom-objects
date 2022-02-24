<?php

declare(strict_types=1);

namespace MauticPlugin\CustomObjectsBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Mautic\CoreBundle\Exception\SchemaException;
use Mautic\IntegrationsBundle\Migration\AbstractMigration;

class Version_0_0_16 extends AbstractMigration
{
    protected function isApplicable(Schema $schema): bool
    {
        $tableName = $this->concatPrefix('custom_field');
        $table     = $schema->getTable($tableName);

        try {
            return $table->hasColumn('required') &&
                null === $table->getColumn('required')->getDefault();
        } catch (SchemaException $e) {
            return false;
        }
    }

    protected function up(): void
    {
        $this->addSql("
            ALTER TABLE {$this->concatPrefix('custom_field')} CHANGE `required` `required` TINYINT(1)  NOT NULL  DEFAULT 0
        ");
    }
}
