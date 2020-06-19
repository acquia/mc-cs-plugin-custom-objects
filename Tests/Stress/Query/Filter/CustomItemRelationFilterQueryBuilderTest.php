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

namespace MauticPlugin\CustomObjectsBundle\Tests\Stress\Query\Filter;

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
     * Duplicate with parent::$em
     * Must be here otherwise it throws
     * Doctrine\ORM\ORMInvalidArgumentException : Detached entity Mautic\LeadBundle\Entity\Lead with ID #1 cannot be removed
     *
     * @var EntityManager
     */
    private $entityManager;

    private $createdContactCount = 0;

    protected function setUp(): void
    {
        parent::setUp();

        /** @var EntityManager $entityManager */
        $this->entityManager = $this->getContainer()->get('doctrine.orm.entity_manager');

        $this->postFixtureSetup();

        $fixturesDirectory = $this->getFixturesDirectory();
        $objects           = $this->loadFixtureFiles(
            [
                $fixturesDirectory . '/custom-item-relation-filter-query-builder-fixture.yml'
            ],
            false,
            null,
            'doctrine'
        );
    }

    /**
     * Performance test with 5 and 10 millions of contacts, 10 and 20 millions of linked custom items linked to
     * another object in 2 layers: contact - custom item - custom item.
     */
    public function test(): void
    {
        $this->generateContacts(5000000);
        $this->buildSegment(1000);
    }

    private function buildSegment(int $contactLimit): void
    {
        $this->generateContacts($contactLimit);

        $microTimeStart = microtime(true);

        $this->runCommand(
            'mautic:segments:update',
            ['--env' => 'test']
        );

        $microTimeEnd = microtime(true);

        $milliseconds = round($microTimeEnd - $microTimeStart, 3);
        echo "Segment for '{$this->createdContactCount}' created in '{$milliseconds}' milliseconds" . PHP_EOL;
    }

    /**
     * @todo Fix segment command to generate data as defined in custom-item-relation-filter-query-builder-fixture.yml
     */
    private function generateContacts(int $count): void
    {
        $this->runCommand(
            'mautic:customobjects:generatesampledata',
            [
                '--env'       => 'test',
                '--object-id' => 1,
                '--limit'     => $count,
                '--force'     => 1,
            ]
        );

        $this->createdContactCount += $count;
    }
}
