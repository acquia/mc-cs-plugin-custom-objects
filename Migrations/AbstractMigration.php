<?php

/*
* @copyright   2019 Mautic, Inc. All rights reserved
* @author      Mautic, Inc.
*
* @link        https://mautic.com
*
* @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
*/

namespace MauticPlugin\CustomObjectsBundle\Migrations;

use Doctrine\ORM\EntityManager;

class AbstractMigration implements MigrationInterface
{
    /**
     * @var EntityManager
     */
    private $entityManager;

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

    public function up(): void
    {
        throw new \RuntimeException('This method must be overridden');
    }

    public function execute(): void
    {
        if (!$this->queries) {
            return;
        }

        $this->entityManager->beginTransaction();
        $connection = $this->entityManager->getConnection();

        foreach($this->queries as $sql) {
            $stmt = $connection->prepare($sql);
            $stmt->execute();
        }

        $this->entityManager->commit();
    }

    /**
     * @param string $sql
     */
    protected function addSql(string $sql): void
    {
        $this->queries[] = $sql;
    }

}