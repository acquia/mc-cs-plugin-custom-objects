<?php

namespace MauticPlugin\CustomObjectsBundle\Tests\Segment\Query\Filter;

use Doctrine\Common\DataFixtures\Purger\ORMPurger;
use Liip\FunctionalTestBundle\Test\WebTestCase;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Segment\RandomParameterName;
use MauticPlugin\CustomObjectsBundle\Segment\Query\Filter\CustomFieldFilterQueryBuilder;

class CustomFieldFilterQueryBuilderTest extends WebTestCase
{
    use FixtureObjectsTrait;

    public function setUp()
    {
        $pluginDirectory = $this->getContainer()->get('kernel')->locateResource('@CustomObjectsBundle');
        $fixturesDirectory = $pluginDirectory . '/Tests/DataFixtures/ORM/Data';

        $objects = $this->loadFixtureFiles([
            $fixturesDirectory . '/roles.yml',
            $fixturesDirectory . '/users.yml',
            $fixturesDirectory . '/leads.yml',
            $fixturesDirectory . '/custom_objects.yml',
            $fixturesDirectory . '/custom_fields.yml',
        ], false, null,'doctrine',ORMPurger::PURGE_MODE_DELETE);

        $this->setFixtureObjects($objects);

        parent::setUp();
    }

    public function tearDown()
    {
        parent::tearDown();

        foreach ($this->getFixturesInUnloadableOrder() as $entity) {
            $this->getContainer()->get('doctrine.orm.entity_manager')->remove($entity);
        }

        $this->getContainer()->get('doctrine.orm.entity_manager')->flush();
    }

    public function testApplyQuery() {
        $queryBuilderService = new CustomFieldFilterQueryBuilder(new RandomParameterName());


    }

    public function testApplyQueryReturnsCorrectResult() {
        $leads = $this->getFixturesByEntityClassName(Lead::class);
        $lead = $this->getFixtureById('user1');
    }
}
