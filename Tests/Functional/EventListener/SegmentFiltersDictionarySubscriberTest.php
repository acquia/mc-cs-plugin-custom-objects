<?php

declare(strict_types=1);

namespace MauticPlugin\CustomObjectsBundle\Tests\Functional\EventListener;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\LeadBundle\Event\SegmentDictionaryGenerationEvent;
use MauticPlugin\CustomObjectsBundle\EventListener\SegmentFiltersDictionarySubscriber;
use MauticPlugin\CustomObjectsBundle\Provider\ConfigProvider;
use MauticPlugin\CustomObjectsBundle\Tests\Functional\DataFixtures\Traits\FixtureObjectsTrait;
use PHPUnit\Framework\MockObject\MockObject;

class SegmentFiltersDictionarySubscriberTest extends MauticMysqlTestCase
{
    use FixtureObjectsTrait;

    public function testGetGenerateSegmentDictionaryReturnsTranslationEvenIfObjectHasNoFields(): void
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

        /** @var ConfigProvider|MockObject $configProviderMock */
        $configProviderMock = $this->createMock(ConfigProvider::class);
        $configProviderMock->expects($this->once())->method('pluginIsEnabled')->willReturn(true);

        $event = new SegmentDictionaryGenerationEvent();

        $subscriber = new SegmentFiltersDictionarySubscriber($this->getContainer()->get('doctrine'), $configProviderMock);
        $subscriber->onGenerateSegmentDictionary($event);

        $COName = 'cmo_'.$this->getFixtureById('custom_object3')->getId();
        $this->assertTrue($event->hasTranslation($COName));
    }
}
