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

    protected function setUp(): void
    {
        parent::setUp();

        /** @var EntityManager $entityManager */
        $this->entityManager = $this->getContainer()->get('doctrine.orm.entity_manager');

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
        /** @var LeadListRepository $segmentRepository */
        $segmentRepository     = $this->container->get('mautic.lead.repository.lead_list');
        $segment               = $segmentRepository->findOneBy(['alias' => 'price-greater-500']);
        $count                 = $segmentRepository->getLeadCount([$segment->getId()]);
        $count                 = (int) $count[$segment->getId()];

        $this->assertSame(
            1,
            $count,
            "Segment with alias 'price-greater-500' should have one contact with email 'rich@toaster.net'"
        );

        // Contact with email 'rich@toaster.net' must be in 'price-greater-500' segment
        /** @var LeadRepository $ccontactRepository */
        $contactRepository = $this->container->get('mautic.lead.repository.lead');
        $contact           = $contactRepository->findOneByEmail('rich@toaster.net');
        /** @var LeadList[] $segments */
        $segments          = $segmentRepository->getLeadLists($contact);

        $this->assertSame(
            1,
            count($segments),
            "Contact with email 'rich@toaster.net' must be in exactly one segment"
        );

        $this->assertSame(
            'price-greater-500',
            $segments[1]->getAlias(),
            "Contact with email 'rich@toaster.net' must be in segment with alias 'price-greater-500'"
        );
    }
}
