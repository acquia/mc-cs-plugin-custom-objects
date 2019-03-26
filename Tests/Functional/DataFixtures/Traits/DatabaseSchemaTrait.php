<?php

declare(strict_types=1);

/*
 * @copyright   2019 Mautic Inc. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\CustomObjectsBundle\Tests\Functional\DataFixtures\Traits;

use Doctrine\ORM\Tools\SchemaTool;
use Doctrine\ORM\EntityManager;

trait DatabaseSchemaTrait
{
    private function createFreshDatabaseSchema(EntityManager $entityManager): void
    {
        $metadata   = $entityManager->getMetadataFactory()->getAllMetadata();
        $schemaTool = new SchemaTool($entityManager);
        $schemaTool->dropDatabase();
        if (!empty($metadata)) {
            $schemaTool->createSchema($metadata);
        }
    }
}
