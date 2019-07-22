<?php

declare(strict_types=1);

namespace MauticPlugin\CustomObjectsBundle\Tests\Functional\EventListener;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\EntityManager;
use Liip\FunctionalTestBundle\Test\WebTestCase;
use Mautic\LeadBundle\Event\SegmentDictionaryGenerationEvent;
use MauticPlugin\CustomObjectsBundle\EventListener\SegmentFiltersDictionarySubscriber;
use MauticPlugin\CustomObjectsBundle\Provider\ConfigProvider;
use MauticPlugin\CustomObjectsBundle\Tests\Functional\DataFixtures\Traits\FixtureObjectsTrait;
use MauticPlugin\CustomObjectsBundle\Tests\Functional\DataFixtures\Traits\DatabaseSchemaTrait;

class SegmentFiltersDictionarySubscriberTest extends WebTestCase
{
    use FixtureObjectsTrait;
    use DatabaseSchemaTrait;

    /**
     * @var EntityManager
     */
    private $entityManager;

    private $registry;

    protected function setUp(): void
    {
        parent::setUp();

        /** @var ManagerRegistry $managerRegistry */
        $this->registry       = $this->getContainer()->get('doctrine');
        $this->entityManager  = $this->registry->getManager();
        $fixturesDirectory    = $this->getFixturesDirectory();

        $this->createFreshDatabaseSchema($this->entityManager);
        $this->postFixtureSetup();

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

    public function testGetGenerateSegmentDictionaryReturnsTranslationEvenIfObjectHasNoFields(): void
    {
        $configProviderMock = $this->createMock(ConfigProvider::class);
        $configProviderMock->expects($this->once())->method('pluginIsEnabled')->willReturn(true);

        $event = new SegmentDictionaryGenerationEvent();

        $subscriber = new SegmentFiltersDictionarySubscriber($this->registry, $configProviderMock);
        $subscriber->onGenerateSegmentDictionary($event);

        $COName = 'cmo_'.$this->getFixtureById('custom_object3')->getId();
        $this->assertTrue($event->hasTranslation($COName));
    }
}
