<?php

declare(strict_types=1);

namespace MauticPlugin\CustomObjectsBundle\Tests\EventListener;

use Doctrine\Common\DataFixtures\Purger\ORMPurger;
use Doctrine\ORM\EntityManagerInterface;
use Mautic\DynamicContentBundle\DynamicContentEvents;
use Mautic\DynamicContentBundle\Event\ContactFiltersEvaluateEvent;
use Mautic\LeadBundle\Entity\Lead;
use MauticPlugin\CustomObjectsBundle\EventListener\DynamicContentSubscriber;
use MauticPlugin\CustomObjectsBundle\Tests\DataFixtures\Traits\FixtureObjectsTrait;

class DynamicContentSubscriberTest extends \Liip\FunctionalTestBundle\Test\WebTestCase
{
    use FixtureObjectsTrait;

    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    protected function setUp(): void
    {
        parent::setUp();

        $pluginDirectory   = $this->getContainer()->get('kernel')->locateResource('@CustomObjectsBundle');
        $fixturesDirectory = $pluginDirectory . '/Tests/DataFixtures/ORM/Data';

        $this->entityManager = $this->getContainer()->get('doctrine.orm.entity_manager');

        $objects = $this->loadFixtureFiles([
            $fixturesDirectory . '/roles.yml',
            $fixturesDirectory . '/users.yml',
            $fixturesDirectory . '/leads.yml',
            $fixturesDirectory . '/custom_objects.yml',
            $fixturesDirectory . '/custom_fields.yml',
            $fixturesDirectory . '/custom_items.yml',
            $fixturesDirectory . '/custom_xref.yml',
            $fixturesDirectory . '/custom_values.yml',
        ], false, null, 'doctrine', ORMPurger::PURGE_MODE_TRUNCATE);

        $this->setFixtureObjects($objects);
    }

    public function testSubscribesToEvent(): void
    {
        $testValue = md5(random_int(0, 10000));

        $dcSubscriberMockBuilder = $this->getMockBuilder(DynamicContentSubscriber::class)->disableOriginalConstructor();
        $dcSubscriberMock        = $dcSubscriberMockBuilder->setMethods([])->getMock();

        $this->assertArrayHasKey(DynamicContentEvents::ON_CONTACTS_FILTER_EVALUATE, $eventSubscriptions = DynamicContentSubscriber::getSubscribedEvents());

        $dcSubscriberMock->expects($this->once())->method('evaluateFilters')->willReturn($testValue);

        $methodName = $eventSubscriptions[DynamicContentEvents::ON_CONTACTS_FILTER_EVALUATE][0];

        $event = new ContactFiltersEvaluateEvent([], new Lead());

        $this->assertSame($testValue, $dcSubscriberMock->{$methodName}($event));
    }
}
