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

class CustomItemRelation3LevelFilterQueryBuilderTestCase extends CustomItemRelationTestCase
{
    use FixtureObjectsTrait;

    protected function setUp(): void
    {
        parent::setUp();

        $fixturesDirectory = $this->getFixturesDirectory();
        $objects           = $this->loadFixtureFiles(
            [
                $fixturesDirectory . '/custom-item-relation-filter-query-builder-fixture-3.yml'
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
}
