<?php

declare(strict_types=1);

namespace MauticPlugin\CustomObjectsBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Mautic\IntegrationsBundle\Migration\AbstractMigration;

class Version_0_0_22 extends AbstractMigration
{
    /**
     * @var string
     */
    private $table = 'custom_item_export_scheduler';

    /**
     * {@inheritdoc}
     */
    protected function isApplicable(Schema $schema): bool
    {
        return !$schema->hasTable($this->concatPrefix($this->table));
    }

    /**
     * {@inheritdoc}
     */
    protected function up(): void
    {
        $this->addSql("CREATE TABLE {$this->concatPrefix($this->table)} (
                `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
                `custom_object_id` int(10) unsigned NOT NULL,
                `user_id` int(10) unsigned,
                PRIMARY KEY (`id`)
            ) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB
        ");
    }
}
