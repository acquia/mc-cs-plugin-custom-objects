<?php

declare(strict_types=1);

namespace MauticPlugin\CustomObjectsBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Schema\SchemaException;
use Mautic\IntegrationsBundle\Migration\AbstractMigration;

class Version_0_0_14 extends AbstractMigration
{
    /**
     * {@inheritdoc}
     */
    protected function isApplicable(Schema $schema): bool
    {
        try {
            return !$schema->getTable($this->concatPrefix('custom_field'))->hasColumn('show_in_custom_object_detail_list');
        } catch (SchemaException $e) {
            return false;
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function up(): void
    {
        $this->addSql("ALTER TABLE {$this->concatPrefix('custom_field')} ADD show_in_custom_object_detail_list TINYINT(1) DEFAULT 1 NOT NULL, ADD show_in_contact_detail_list TINYINT(1) DEFAULT 1 NOT NULL");
        $this->addSql("UPDATE {$this->concatPrefix('custom_field')} SET show_in_custom_object_detail_list = 1, show_in_contact_detail_list = 1");
    }
}
