<?php

declare(strict_types=1);

namespace MauticPlugin\CustomObjectsBundle\Tests\Functional\Segment\Query\Filter;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\LeadBundle\Entity\LeadList;
use MauticPlugin\CustomObjectsBundle\Provider\ConfigProvider;
use MauticPlugin\CustomObjectsBundle\Tests\Functional\DataFixtures\Traits\FixtureObjectsTrait;
use PHPUnit\Framework\Assert;

class CustomObjectMergedFilterQueryBuilderTest extends MauticMysqlTestCase
{
    use FixtureObjectsTrait;

    protected function setUp(): void
    {
        $this->configParams['custom_object_merge_filter']                                      = true;
        $this->configParams[ConfigProvider::CONFIG_PARAM_ITEM_VALUE_TO_CONTACT_RELATION_LIMIT] = 0;
        parent::setUp();
    }

    public function testMergedSegmentFilters(): void
    {
        $fixturesDirectory = $this->getFixturesDirectory();
        $objects           = $this->loadFixtureFiles([
            $fixturesDirectory.'/leads.yml',
            $fixturesDirectory.'/custom_objects.yml',
            $fixturesDirectory.'/custom_fields.yml',
            $fixturesDirectory.'/custom_items.yml',
            $fixturesDirectory.'/custom_xref.yml',
            $fixturesDirectory.'/custom_values.yml',
        ]);
        $this->setFixtureObjects($objects);

        $customField1 = $this->getFixtureById('custom_field1')->getId();
        $customField2 = $this->getFixtureById('custom_field1')->getId();

        $filters = [
            [
                'glue'       => 'and',
                'object'     => 'custom_object',
                'type'       => 'text',
                'field'      => 'cmf_'.$customField1,
                'properties' => ['filter' => 'l'],
                'operator'   => 'startsWith',
            ],
            [
                'glue'       => 'and',
                'object'     => 'custom_object',
                'type'       => 'text',
                'field'      => 'cmf_'.$customField2,
                'properties' => ['filter' => 'e'],
                'operator'   => 'endsWith',
            ],
        ];
        $segment = $this->createSegment($filters);

        $applicationTester = $this->testSymfonyCommand('mautic:segments:update', ['-i' => $segment->getId(), '--env' => 'test']);
        Assert::assertSame(0, $applicationTester->getStatusCode());
        Assert::assertStringContainsString('3 total contact(s) to be added in batches of 300', $applicationTester->getDisplay());
    }

    /**
     * @param mixed[] $filters
     */
    private function createSegment(array $filters): LeadList
    {
        $segment = new LeadList();
        $segment->setFilters($filters);
        $segment->setName('Segment A');
        $segment->setAlias('segment-a');
        $this->em->persist($segment);
        $this->em->flush();

        return $segment;
    }
}
