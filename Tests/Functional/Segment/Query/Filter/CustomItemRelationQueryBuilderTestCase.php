<?php

declare(strict_types=1);

namespace MauticPlugin\CustomObjectsBundle\Tests\Functional\Segment\Query\Filter;

use InvalidArgumentException;
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\LeadBundle\Entity\LeadList;
use Mautic\LeadBundle\Entity\LeadListRepository;
use Mautic\LeadBundle\Entity\LeadRepository;
use MauticPlugin\CustomObjectsBundle\Provider\ConfigProvider;
use MauticPlugin\CustomObjectsBundle\Tests\Functional\DataFixtures\Traits\FixtureObjectsTrait;

class CustomItemRelationQueryBuilderTestCase extends MauticMysqlTestCase
{
    use FixtureObjectsTrait;

    /**
     * @var CoreParametersHelper
     */
    private $coreParametersHelper;

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

        $this->coreParametersHelper = self::$container->get('mautic.helper.core_parameters');
        $this->segmentRepository    = self::$container->get('mautic.lead.repository.lead_list');
        $this->contactRepository    = self::$container->get('mautic.lead.repository.lead');
    }

    protected function beforeBeginTransaction(): void
    {
        $this->resetAutoincrement([
            'custom_object',
            'custom_field',
        ]);
    }

    /**
     * Limit of relations must be set here to 1.
     *
     * @see plugins/CustomObjectsBundle/Config/config.php::CONFIG_PARAM_ITEM_VALUE_TO_CONTACT_RELATION_LIMIT
     */
    public function testApplyQuery1stLevel(): void
    {
        $this->loadFixtureFiles([
            $this->getFixturesDirectory().'/custom-item-relation-filter-query-builder-fixture-1.yml',
        ]);

        $this->runCommand(
            'mautic:segments:update',
            ['--env' => 'test']
        );

        $this->assertLeadCountBySegmentAlias(1, 'price-eq-1000');
        $this->assertContactIsInSegment('direct@relation.net', 'price-eq-1000');

        // custom item name
        $this->assertLeadCountBySegmentAlias(1, 'order-plug-name-eq');
        $this->assertContactIsInSegment('direct@relation.net', 'order-plug-name-eq');
    }

    public function testApplyQuery2ndLevel(): void
    {
        $this->loadFixtureFiles([
            $this->getFixturesDirectory().'/custom-item-relation-filter-query-builder-fixture-2.yml',
        ]);

        $limit = $this->coreParametersHelper->get(ConfigProvider::CONFIG_PARAM_ITEM_VALUE_TO_CONTACT_RELATION_LIMIT);

        $this->assertTrue(
            1 < $limit,
            ConfigProvider::CONFIG_PARAM_ITEM_VALUE_TO_CONTACT_RELATION_LIMIT." must be set to higher value than 1 to run this test. Current value is '{$limit}' "
        );

        $this->runCommand(
            'mautic:segments:update',
            ['--env' => 'test']
        );

        // custom item name
        $this->assertLeadCountBySegmentAlias(1, 'order-plug-name-eq');
        $this->assertContactIsInSegment('poor@plug.net', 'order-plug-name-eq');

        // price eq 1000
        $this->assertLeadCountBySegmentAlias(2, 'price-eq-1000');
        $this->assertContactIsInSegment('rich@toaster.net', 'price-eq-1000');
        $this->assertContactIsInSegment('direct@relation.net', 'price-eq-1000');

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

        // option - multiselect
        $this->assertLeadCountBySegmentAlias(2, 'option-in-1');
        $this->assertContactIsInSegment('rich@toaster.net', 'option-in-1');
        $this->assertContactIsInSegment('direct@relation.net', 'option-in-1');

        // text
        $this->assertLeadCountBySegmentAlias(2, 'text-eq-text');
        $this->assertContactIsInSegment('rich@toaster.net', 'text-eq-text');
        $this->assertContactIsInSegment('direct@relation.net', 'text-eq-text');

        // combined
        $this->assertLeadCountBySegmentAlias(2, 'combined');
        $this->assertContactIsInSegment('rich@toaster.net', 'combined');
        $this->assertContactIsInSegment('direct@relation.net', 'date-lt-1990');
    }

    public function testApplyQuery3ndLevel(): void
    {
        $this->loadFixtureFiles([
            $this->getFixturesDirectory().'/custom-item-relation-filter-query-builder-fixture-3.yml',
        ]);

        $limit = $this->coreParametersHelper->get(ConfigProvider::CONFIG_PARAM_ITEM_VALUE_TO_CONTACT_RELATION_LIMIT);

        $this->assertTrue(
            2 < $limit,
            ConfigProvider::CONFIG_PARAM_ITEM_VALUE_TO_CONTACT_RELATION_LIMIT." must be set to higher value than 2 to run this test. Current value is '{$limit}' "
        );

        $this->runCommand(
            'mautic:segments:update',
            ['--env' => 'test']
        );

        $this->assertLeadCountBySegmentAlias(3, 'price-eq-1000');
        // 1st level
        $this->assertContactIsInSegment('direct@relation.net', 'price-eq-1000');
        // 2nd level
        $this->assertContactIsInSegment('rich@toaster.net', 'price-eq-1000');
        // 3rd level
        $this->assertContactIsInSegment('poor@toaster.net', 'price-eq-1000');
    }

    private function assertLeadCountBySegmentAlias(int $expectedLeadCount, string $segmentAlias): void
    {
        $segment = $this->segmentRepository->findOneBy(['alias' => $segmentAlias]);

        if (!$segment) {
            throw new InvalidArgumentException("No segment with alias '{$segmentAlias}' found");
        }

        $count = $this->segmentRepository->getLeadCount([$segment->getId()]);
        $count = (int) $count[$segment->getId()];

        $this->assertSame(
            $expectedLeadCount,
            $count,
            "Segment with alias '{$segmentAlias}' should have '{$expectedLeadCount}' contact count. Has '{$count}'"
        );
    }

    private function assertContactIsInSegment(string $contactEmail, string $segmentAlias): void
    {
        $contact = $this->contactRepository->findOneByEmail($contactEmail);
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
