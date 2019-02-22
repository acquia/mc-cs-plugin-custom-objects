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

class AbstractMigration implements MigrationInterface
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
     * @var array
     */
    private $queries = [];

    /**
     * @param EntityManager $entityManager
     * @param string        $tablePrefix
     */
    public function __construct(EntityManager $entityManager, string $tablePrefix)
    {
        $this->entityManager = $entityManager;
        $this->tablePrefix = $tablePrefix;
    }

    /**
     * {@inheritdoc}
     */
    public function isApplicable(): bool
    {
        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function up(): void
    {
        // Implement it in child class
    }

    /**
     * {@inheritdoc}
     * @throws \Doctrine\DBAL\DBALException
     */
    public function execute(): void
    {
        if (!$this->queries) {
            return;
        }

        if (!$this->isApplicable()) {
            return;
        }

        $connection = $this->entityManager->getConnection();

        foreach($this->queries as $sql) {
            $stmt = $connection->prepare($sql);
            $stmt->execute();
        }
    }

    /**
     * @param string $sql
     */
    protected function addSql(string $sql): void
    {
        $this->queries[] = $sql;
    }
}