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
     * @var \Doctrine\ORM\EntityRepository|null
     */
    private $contactRepository;

    protected function setUp(): void
    {
        parent::setUp();

        /** @var EntityManager $entityManager */
        $this->entityManager = $this->getContainer()->get('doctrine.orm.entity_manager');

        /** @var LeadListRepository $segmentRepository */
        $this->segmentRepository     = $this->container->get('mautic.lead.repository.lead_list');

        /** @var LeadRepository $contactRepository */
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

    public function testApplyQuery(): void
    {
        $this->runCommand(
            'mautic:segments:update',
            ['--env' => 'test']
        );

        // Segment 'price-greater-500' has exactly one contact
        $this->assertLeadCountBySegmentAlias(1, 'price-greater-500');
        // Contact with email 'rich@toaster.net' must be in 'price-greater-500' segment
        $this->assertContactIsInSegment('rich@toaster.net', 'price-greater-500');

        $this->assertLeadCountBySegmentAlias(1, 'price-eq-500');
        $this->assertContactIsInSegment('poor@plug.net', 'price-eq-500');

        $this->assertLeadCountBySegmentAlias(0, 'price-greater-1000');
        $this->assertLeadCountBySegmentAlias(2, 'price-lte-1000');
        $this->assertLeadCountBySegmentAlias(0, 'price-lt-500');

        // @TODO add these relations too
        // custom_field_value_date
        // custom_field_value_datetime
        // custom_field_value_option
        // custom_field_value_text
    }

    private function assertLeadCountBySegmentAlias(int $expectedLeadCount, string $segmentAlias): void
    {
        $segment = $this->segmentRepository->findOneBy(['alias' => 'price-greater-500']);
        $count   = $this->segmentRepository->getLeadCount([$segment->getId()]);
        $count   = (int) $count[$segment->getId()];

        $this->assertSame(
            1,
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
