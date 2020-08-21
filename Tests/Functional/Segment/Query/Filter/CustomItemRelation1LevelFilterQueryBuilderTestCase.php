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

use MauticPlugin\CustomObjectsBundle\Repository\DbalQueryTrait;
use MauticPlugin\CustomObjectsBundle\Tests\Functional\DataFixtures\Traits\DatabaseSchemaTrait;
use MauticPlugin\CustomObjectsBundle\Tests\Functional\DataFixtures\Traits\FixtureObjectsTrait;

class CustomItemRelation1LevelFilterQueryBuilderTestCase extends CustomItemRelationTestCase
{
    use FixtureObjectsTrait;
    use DbalQueryTrait;
    use DatabaseSchemaTrait;

    protected function setUp(): void
    {
        parent::setUp();

        $fixturesDirectory = $this->getFixturesDirectory();
        $objects = $this->loadFixtureFiles(
            [
                $fixturesDirectory . '/custom-item-relation-filter-query-builder-fixture-1.yml'
            ],
            false,
            null,
            'doctrine'
        );

        $this->setFixtureObjects($objects);
    }

    /**
     * Limit of relations must be set here to 1
     * @see plugins/CustomObjectsBundle/Config/config.php::CONFIG_PARAM_ITEM_VALUE_TO_CONTACT_RELATION_LIMIT
     */
    public function testApplyQuery1stLevel(): void
    {
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
}
