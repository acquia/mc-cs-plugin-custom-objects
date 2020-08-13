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

use MauticPlugin\CustomObjectsBundle\Provider\ConfigProvider;
use MauticPlugin\CustomObjectsBundle\Tests\Functional\DataFixtures\Traits\FixtureObjectsTrait;

class CustomItemRelation2LevelFilterQueryBuilderTestCase extends CustomItemRelationTestCase
{
    use FixtureObjectsTrait;

    protected function setUp(): void
    {
        parent::setUp();

        $fixturesDirectory = $this->getFixturesDirectory();
        $objects           = $this->loadFixtureFiles(
            [
                $fixturesDirectory . '/custom-item-relation-filter-query-builder-fixture-2.yml'
            ],
            false,
            null,
            'doctrine'
        );

        $this->setFixtureObjects($objects);
    }

    public function testApplyQuery2ndLevel(): void
    {
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
    }
}
