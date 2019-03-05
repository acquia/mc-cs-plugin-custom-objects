<?php

declare(strict_types=1);

namespace MauticPlugin\CustomObjectsBundle\Tests\EventListener;

use Doctrine\Common\DataFixtures\Purger\ORMPurger;
use Mautic\DynamicContentBundle\DynamicContentEvents;
use MauticPlugin\CustomObjectsBundle\EventListener\DynamicContentSubscriber;
use MauticPlugin\CustomObjectsBundle\Tests\DataFixtures\Traits\FixtureObjectsTrait;

class DynamicContentSubscriberTest extends \Liip\FunctionalTestBundle\Test\WebTestCase
{
    use FixtureObjectsTrait;

    protected function setUp(): void
    {
        parent::setUp();

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
        ], false, null, 'doctrine');

        $this->setFixtureObjects($objects);
    }

    public function testSubscribesToEvent(): void
    {
        $eventSubscriptions = DynamicContentSubscriber::getSubscribedEvents();

        $methodName = $eventSubscriptions[DynamicContentEvents::ON_CONTACTS_FILTER_EVALUATE][0];

        $this->assertSame('evaluateFilters', $methodName);
    }
}
