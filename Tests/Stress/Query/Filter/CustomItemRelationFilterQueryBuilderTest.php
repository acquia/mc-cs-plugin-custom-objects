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

use Mautic\CoreBundle\Test\MauticWebTestCase;
use MauticPlugin\CustomObjectsBundle\Tests\Functional\DataFixtures\Traits\DatabaseSchemaTrait;

class CustomItemRelationFilterQueryBuilderTest extends MauticWebTestCase
{
    use DatabaseSchemaTrait;

    private $createdContactCount = 0;

    protected function setUp(): void
    {
        parent::setUp();

        $this->createFreshDatabaseSchema($this->em);
    }

    public function testApplyQuery(): void
    {
        $this->testSegmentBuildTime(10);
        $this->testSegmentBuildTime(100);
        $this->testSegmentBuildTime(1000);
        $this->testSegmentBuildTime(100000);
        $this->testSegmentBuildTime(1000000);
        $this->testSegmentBuildTime(5000000);
        $this->testSegmentBuildTime(10000000);
    }

    private function testSegmentBuildTime(int $contactLimit): void
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

    private function generateContacts(int $contactLimit): void
    {
        sleep(1);
    }
}