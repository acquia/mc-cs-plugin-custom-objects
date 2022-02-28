<?php

declare(strict_types=1);

namespace MauticPlugin\CustomObjectsBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Mautic\CoreBundle\Exception\SchemaException;
use Mautic\IntegrationsBundle\Migration\AbstractMigration;
use MauticPlugin\CustomObjectsBundle\Entity\CustomObject;

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
        $tableCustomObject = $this->concatPrefix($this->table);

        try {
            return !$schema->getTable($tableCustomObject)->hasColumn('type') ||
                !$schema->getTable($tableCustomObject)->hasColumn('master_object');
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
        $default           = CustomObject::TYPE_MASTER;

        $this->addSql("ALTER TABLE {$tableCustomObject} ADD type INT DEFAULT {$default}, ADD INDEX (type)");
        $this->addSql("ALTER TABLE {$tableCustomObject} ADD master_object INT, ADD INDEX (master_object)");
    }
}
