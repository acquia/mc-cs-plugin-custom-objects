<?php

declare(strict_types=1);

namespace MauticPlugin\CustomObjectsBundle\Tests\Functional\EventListener;

use Doctrine\ORM\Tools\SchemaTool;
use Mautic\DynamicContentBundle\DynamicContentEvents;
use MauticPlugin\CustomObjectsBundle\EventListener\DynamicContentSubscriber;
use MauticPlugin\CustomObjectsBundle\Tests\Functional\DataFixtures\Traits\FixtureObjectsTrait;

class DynamicContentSubscriberTest extends \Liip\FunctionalTestBundle\Test\WebTestCase
{
    use FixtureObjectsTrait;

    protected function setUp(): void
    {
        parent::setUp();

        $em         = $this->getContainer()->get('doctrine')->getManager();
        $metadata   = $em->getMetadataFactory()->getAllMetadata();
        $schemaTool = new SchemaTool($em);
        $schemaTool->dropDatabase();
        if (!empty($metadata)) {
            $schemaTool->createSchema($metadata);
        }
        $this->postFixtureSetup();

        $pluginDirectory   = $this->getContainer()->get('kernel')->locateResource('@CustomObjectsBundle');
        $fixturesDirectory = $pluginDirectory.'/Tests/DataFixtures/ORM/Data';

        $objects = $this->loadFixtureFiles([
            $fixturesDirectory.'/roles.yml',
            $fixturesDirectory.'/users.yml',
            $fixturesDirectory.'/leads.yml',
            $fixturesDirectory.'/custom_objects.yml',
            $fixturesDirectory.'/custom_fields.yml',
            $fixturesDirectory.'/custom_items.yml',
            $fixturesDirectory.'/custom_xref.yml',
            $fixturesDirectory.'/custom_values.yml',
        ], false, null, 'doctrine'); //,ORMPurger::PURGE_MODE_DELETE);

        $this->setFixtureObjects($objects);
    }

    public function testSubscribesToEvent(): void
    {
        $eventSubscriptions = DynamicContentSubscriber::getSubscribedEvents();

        $methodName = $eventSubscriptions[DynamicContentEvents::ON_CONTACTS_FILTER_EVALUATE][0];

        $this->assertSame('evaluateFilters', $methodName);
    }
}
