<?php

namespace MauticPlugin\CustomObjectsBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Mautic\CoreBundle\Exception\SchemaException;
use Mautic\IntegrationsBundle\Migration\AbstractMigration;
use MauticPlugin\CustomObjectsBundle\Entity\CustomObject;

class Version_0_0_13 extends AbstractMigration
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
        return true;
    }

    /**
     * {@inheritdoc}
     */
    protected function up(): void
    {
        $tableCustomObject = $this->concatPrefix($this->table);
        $default = CustomObject::TYPE_MASTER;

        $this->addSql("ALTER TABLE {$tableCustomObject} MODIFY COLUMN type INT DEFAULT {$default}");
    }
}
