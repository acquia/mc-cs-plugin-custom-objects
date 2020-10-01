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
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\CoreBundle\Test\MauticWebTestCase;
use Mautic\LeadBundle\Entity\LeadList;
use Mautic\LeadBundle\Entity\LeadListRepository;
use Mautic\LeadBundle\Entity\LeadRepository;
use MauticPlugin\CustomObjectsBundle\Repository\DbalQueryTrait;
use MauticPlugin\CustomObjectsBundle\Tests\Functional\DataFixtures\Traits\DatabaseSchemaTrait;
use MauticPlugin\CustomObjectsBundle\Tests\Functional\DataFixtures\Traits\FixtureObjectsTrait;

abstract class CustomItemRelationTestCase extends MauticWebTestCase
{
    use FixtureObjectsTrait;
    use DbalQueryTrait;
    use DatabaseSchemaTrait;

    /**
     * @var CoreParametersHelper
     */
    protected $coreParametersHelper;

    /**
     * Duplicate with parent::$em
     * Must be here otherwise it throws
     * Doctrine\ORM\ORMInvalidArgumentException : Detached entity Mautic\LeadBundle\Entity\Lead with ID #1 cannot be removed
     *
     * @var EntityManager
     */
    protected $entityManager;

    /**
     * @var LeadListRepository
     */
    protected $segmentRepository;

    /**
     * @var LeadRepository
     */
    protected $contactRepository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->coreParametersHelper = $this->getContainer()->get('mautic.helper.core_parameters');

        $this->entityManager = $this->getContainer()->get('doctrine.orm.entity_manager');
        $this->segmentRepository = $this->container->get('mautic.lead.repository.lead_list');
        $this->contactRepository = $this->container->get('mautic.lead.repository.lead');

        $this->createFreshDatabaseSchema($this->entityManager);
        $this->postFixtureSetup();
    }

    protected function tearDown(): void
    {
        foreach ($this->getFixturesInUnloadableOrder() as $entity) {
            $this->entityManager->remove($entity);
        }

        $this->entityManager->flush();

        parent::tearDown();
    }

    protected function assertLeadCountBySegmentAlias(int $expectedLeadCount, string $segmentAlias): void
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

    protected function assertContactIsInSegment(string $contactEmail, string $segmentAlias): void
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