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

use Mautic\CoreBundle\Factory\MauticFactory;

class Engine
{
    /**
     * @var MauticFactory
     */
    private $mauticFactory;

    /**
     * @param MauticFactory $mauticFactory
     */
    public function __construct(MauticFactory $mauticFactory)
    {
        $this->mauticFactory = $mauticFactory;
    }

    public function up(string $version): void
    {
        $version = str_replace('.', '_', $version);
        $migrationClass = "Version_{$version}";
        $phpFile = __DIR__ . '/' . $migrationClass . '.php';
        $migrationClass = "MauticPlugin\CustomObjectsBundle\Migrations\\$migrationClass";

        if (!file_exists($phpFile)) {
            return;
        }

        require_once $phpFile;

        /** @var AbstractMigration $migration */
        $migration = new $migrationClass(
            $this->mauticFactory->getEntityManager(),
            $this->mauticFactory->getParameter('mautic.db_table_prefix')
        );

        $migration->up();
        $migration->execute();
    }
}