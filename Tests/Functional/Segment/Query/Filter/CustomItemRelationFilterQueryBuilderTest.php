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

use Doctrine\ORM\EntityManager;
use Mautic\CoreBundle\Test\MauticWebTestCase;
use Mautic\LeadBundle\Entity\LeadList;
use Mautic\LeadBundle\Entity\LeadListRepository;
use Mautic\LeadBundle\Entity\LeadRepository;
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

    /**
     * @var LeadListRepository
     */
    private $segmentRepository;

    /**
     * @var LeadRepository
     */
    private $contactRepository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->entityManager = $this->getContainer()->get('doctrine.orm.entity_manager');
        $this->segmentRepository = $this->container->get('mautic.lead.repository.lead_list');
        $this->contactRepository = $this->container->get('mautic.lead.repository.lead');

        $this->createFreshDatabaseSchema($this->entityManager);
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

        $this->setFixtureObjects($objects);
    }

    protected function tearDown(): void
    {
        foreach ($this->getFixturesInUnloadableOrder() as $entity) {
            $this->entityManager->remove($entity);
        }

        $this->entityManager->flush();

        parent::tearDown();
    }

    /**
     * Limit of relations must be set here to 1
     * @see plugins/CustomObjectsBundle/Config/config.php::CONFIG_PARAM_ITEM_VALUE_TO_CONTACT_RELATION_LIMIT
     * This is not possible right now, to change this value and rerun app.
     */
    public function testApplyQuery1stLevel(): void
    {
        $this->markTestSkipped('Multilevel testing not implemented yet');

        $this->runCommand(
            'mautic:segments:update',
            ['--env' => 'test']
        );

        // custom item name
        $this->assertLeadCountBySegmentAlias(1, 'order-plug-name-eq');
        $this->assertContactIsInSegment('poor@plug.net', 'order-plug-name-eq');

        $this->assertLeadCountBySegmentAlias(1, 'price-eq-1000');
        $this->assertContactIsInSegment('direct@relation.net', 'price-eq-1000');
    }

    public function testApplyQuery2ndLevel(): void
    {
        $this->runCommand(
            'mautic:segments:update',
            ['--env' => 'test']
        );

        // custom item name
        $this->assertLeadCountBySegmentAlias(1, 'order-plug-name-eq');
        $this->assertContactIsInSegment('poor@plug.net', 'order-plug-name-eq');

        // date
        $this->assertLeadCountBySegmentAlias(2, 'date-lt-1990');
        $this->assertContactIsInSegment('rich@toaster.net', 'date-lt-1990');
        $this->assertContactIsInSegment('direct@relation.net', 'date-lt-1990');

        // datetime
        $this->assertLeadCountBySegmentAlias(1, 'datetime-gt-1990');
        $this->assertContactIsInSegment('poor@plug.net', 'datetime-gt-1990');

        // int
        // Segment 'price-greater-500' has exactly two contacts
        $this->assertLeadCountBySegmentAlias(2, 'price-greater-500');
        // Contact with email 'rich@toaster.net' must be in 'price-greater-500' segment
        $this->assertContactIsInSegment('rich@toaster.net', 'price-greater-500');
        // Direct relation of contact to product
        $this->assertContactIsInSegment('direct@relation.net', 'price-greater-500');

        $this->assertLeadCountBySegmentAlias(1, 'price-eq-500');
        $this->assertContactIsInSegment('poor@plug.net', 'price-eq-500');

        $this->assertLeadCountBySegmentAlias(0, 'price-greater-1000');
        $this->assertLeadCountBySegmentAlias(3, 'price-lte-1000');

        $this->assertLeadCountBySegmentAlias(0, 'price-lt-500');

//        // option - multiselect
//        $this->assertLeadCountBySegmentAlias(2, 'option-in-1');
//        $this->assertContactIsInSegment('rich@toaster.net', 'option-in-1');
//        $this->assertContactIsInSegment('direct@relation.net', 'option-in-1');

        // text
        $this->assertLeadCountBySegmentAlias(2, 'text-eq-text');
        $this->assertContactIsInSegment('rich@toaster.net', 'text-eq-text');
        $this->assertContactIsInSegment('direct@relation.net', 'text-eq-text');
    }

    private function assertLeadCountBySegmentAlias(int $expectedLeadCount, string $segmentAlias): void
    {
        $segment = $this->segmentRepository->findOneBy(['alias' => $segmentAlias]);

        if (!$segment) {
            throw new \InvalidArgumentException("No segment with alias '{$segmentAlias}' found");
        }

        $count   = $this->segmentRepository->getLeadCount([$segment->getId()]);
        $count   = (int) $count[$segment->getId()];

        $this->assertSame(
            $expectedLeadCount,
            $count,
            "Segment with alias '{$segmentAlias}' should have '{$expectedLeadCount}' contact count. Has '{$count}'"
        );
    }

    private function assertContactIsInSegment(string $contactEmail, string $segmentAlias): void
    {
        $contact  = $this->contactRepository->findOneByEmail($contactEmail);
        /** @var LeadList[] $segments */
        $segments = $this->segmentRepository->getLeadLists($contact->getId());

        $found = false;

        foreach ($segments as $segment) {
            if ($segment->getAlias() === $segmentAlias) {
                $found = true;
                break;
            }
        }

        $this->assertTrue(
            $found,
            "Contact with email '{$contactEmail}' must be in segment with alias '{$segmentAlias}'"
        );
    }
}
