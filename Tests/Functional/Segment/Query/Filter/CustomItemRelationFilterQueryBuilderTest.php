<?php

declare(strict_types=1);

/*
 * @copyright   2020 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\CustomObjectsBundle\Tests\Functional\Segment\Query\Filter;

use Doctrine\Common\DataFixtures\Purger\ORMPurger;
use Doctrine\ORM\EntityManager;
use Mautic\CoreBundle\Test\MauticWebTestCase;
use MauticPlugin\CustomObjectsBundle\Repository\DbalQueryTrait;
use MauticPlugin\CustomObjectsBundle\Tests\Functional\DataFixtures\Traits\DatabaseSchemaTrait;
use MauticPlugin\CustomObjectsBundle\Tests\Functional\DataFixtures\Traits\FixtureObjectsTrait;

class CustomItemRelationFilterQueryBuilderTest extends MauticWebTestCase
{
    use FixtureObjectsTrait;
    use DbalQueryTrait;
    use DatabaseSchemaTrait;

    /**
     * @var EntityManager
     */
    private $entityManager;

    protected function setUp(): void
    {
        parent::setUp();

        /** @var EntityManager $entityManager */
        $this->entityManager = $this->getContainer()->get('doctrine.orm.entity_manager');

        $this->createFreshDatabaseSchema($this->entityManager);
        $this->postFixtureSetup();

        $fixturesDirectory = $this->getFixturesDirectory();
        $objects           = $this->loadFixtureFiles([
            $fixturesDirectory . '/CustomItemRelationFilterQueryBuilderFixture.yml'
        ], false, null, 'doctrine',ORMPurger::PURGE_MODE_DELETE);

        $this->setFixtureObjects($objects);
    }

    protected function tearDown(): void
    {
//        foreach ($this->getFixturesInUnloadableOrder() as $entity) {
//            $this->entityManager->remove($entity);
//        }
//
//        $this->entityManager->flush();
//
//        parent::tearDown();
    }

    public function testApplyQuery(): void
    {
        $this->assertTrue(true);
    }
}