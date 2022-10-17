<?php

declare(strict_types=1);

namespace MauticPlugin\CustomObjectsBundle\Migrations;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\DBAL\Schema\ForeignKeyConstraint;
use Doctrine\DBAL\Schema\Schema;
use Mautic\IntegrationsBundle\Migration\AbstractMigration;

class Version_0_0_19 extends AbstractMigration
{
    public const FOREIGN_KEY_TO_DELETE = '/^FK_\d+594D0CC2$/';

    /** @var Schema */
    private $schema;

    protected function isApplicable(Schema $schema): bool
    {
        $this->schema          = $schema;
        $hasCoMasterForeignKey = $this->schema->getTable($this->concatPrefix('custom_object'))->hasForeignKey('FK_CO_MASTER_OBJECT');

        return (0 < count($this->getForeignKeysToDrop())) && $hasCoMasterForeignKey;
    }

    protected function up(): void
    {
        foreach ($this->getForeignKeysToDrop() as $foreignKeyToDrop) {
            if ($this->schema->getTable($this->concatPrefix('custom_object'))->hasForeignKey($foreignKeyToDrop->getName())) {
                $this->addSql("ALTER TABLE {$this->concatPrefix('custom_object')} DROP FOREIGN KEY {$foreignKeyToDrop->getName()};");
            }

            $indexName = $this->getIndexName($foreignKeyToDrop);
            if ($this->schema->getTable($this->concatPrefix('custom_object'))->hasIndex($indexName)) {
                $this->addSql("ALTER TABLE {$this->concatPrefix('custom_object')} DROP INDEX {$indexName};");
            }
        }
    }

    /**
     * @return ForeignKeyConstraint[]
     */
    private function getForeignKeysToDrop(): array
    {
        $foreignKeys = new ArrayCollection($this->schema->getTable($this->concatPrefix('custom_object'))->getForeignKeys());

        return $foreignKeys->filter(function (ForeignKeyConstraint $foreignKeyConstraint): bool {
            return 1 === preg_match(static::FOREIGN_KEY_TO_DELETE, $foreignKeyConstraint->getName());
        })->toArray();
    }

    private function getIndexName(ForeignKeyConstraint $foreignKeyConstraint): string
    {
        return str_replace('FK_', 'UNIQ_', $foreignKeyConstraint->getName());
    }
}
