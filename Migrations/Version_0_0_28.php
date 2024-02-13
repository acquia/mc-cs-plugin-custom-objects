<?php

declare(strict_types=1);

namespace MauticPlugin\CustomObjectsBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Mautic\IntegrationsBundle\Migration\AbstractMigration;

final class Version_0_0_28 extends AbstractMigration
{
    private Schema $schema;

    private string $table = 'custom_object';

    protected function up(): void
    {
        $this->addSql("
            ALTER TABLE `{$this->concatPrefix($this->table)}` 
            CHANGE `description` `description` TEXT
        ");
    }

    protected function isApplicable(Schema $schema): bool
    {
        $this->schema = $schema;

        return true;
    }

    public function down(Schema $schema): void
    {
        $this->addSql("
            ALTER TABLE `{$this->concatPrefix($this->table)}` 
            CHANGE `description` `description` VARCHAR(191)
        ");
    }
}
