<?php

declare(strict_types=1);

/*
* @copyright   2019 Mautic, Inc. All rights reserved
* @author      Mautic, Inc.
*
* @link        https://mautic.com
*
* @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
*/

namespace MauticPlugin\CustomObjectsBundle\Migration;

use Doctrine\ORM\EntityManager;
use Doctrine\DBAL\Schema\Schema;

abstract class AbstractMigration implements MigrationInterface
{
    /**
     * @var EntityManager
     */
    protected $entityManager;

    /**
     * @var string
     */
    protected $tablePrefix;

    /**
     * @var string[]
     */
    private $queries = [];

    /**
     * @param EntityManager $entityManager
     * @param string        $tablePrefix
     */
    public function __construct(EntityManager $entityManager, string $tablePrefix)
    {
        $this->entityManager = $entityManager;
        $this->tablePrefix   = $tablePrefix;
    }

    /**
     * {@inheritdoc}
     */
    public function shouldExecute(): bool
    {
        return $this->isApplicable($this->entityManager->getConnection()->getSchemaManager()->createSchema());
    }

    /**
     * {@inheritdoc}
     *
     * @throws \Doctrine\DBAL\DBALException
     */
    public function execute(): void
    {
        $this->up();

        if (!$this->queries) {
            return;
        }

        $connection = $this->entityManager->getConnection();

        foreach ($this->queries as $sql) {
            $stmt = $connection->prepare($sql);
            $stmt->execute();
        }
    }

    /**
     * Define in the child migration whether the migration should be executed.
     * Check if the migration is applied in the schema already.
     *
     * @param Schema $schema
     *
     * @return bool
     */
    abstract protected function isApplicable(Schema $schema): bool;

    /**
     * Define queries for migration up.
     */
    abstract protected function up(): void;

    /**
     * @param string $sql
     */
    protected function addSql(string $sql): void
    {
        $this->queries[] = $sql;
    }
}
