<?php

namespace MauticPlugin\CustomObjectsBundle\Tests\EventListener;


use Doctrine\ORM\EntityManager;
use Mautic\DynamicContentBundle\Event\ContactFiltersEvaluateEvent;
use Mautic\LeadBundle\Segment\ContactSegmentFilterFactory;
use MauticPlugin\CustomObjectsBundle\EventListener\DynamicContentSubscriber;
use MauticPlugin\CustomObjectsBundle\Helper\QueryFilterHelper;
use MauticPlugin\CustomObjectsBundle\Provider\ConfigProvider;

class DynamicContentSubscriberTest extends \PHPUnit_Framework_TestCase
{
    /** @var ConfigProvider|\PHPUnit_Framework_MockObject_MockObject */
    private $configProviderMock;

    /** @var ContactSegmentFilterFactory|\PHPUnit_Framework_MockObject_MockObject */
    private $segmentFilterFactoryMock;

    /** @var QueryFilterHelper|\PHPUnit_Framework_MockObject_MockObject */
    private $queryFilterHelperMock;

    /** @var EntityManager|\PHPUnit_Framework_MockObject_MockObject */
    private $entityManagerMock;

    /** @var ContactFiltersEvaluateEvent|\PHPUnit_Framework_MockObject_MockObject */
    private $evaluateEvent;

    /** @var DynamicContentSubscriber */
    private $dynamicContentSubscriber;

    public function setUp()
    {
        parent::setUp();

        $this->configProviderMock = $this->createMock(ConfigProvider::class);
        $this->entityManagerMock = $this->createMock(EntityManager::class);
        $this->segmentFilterFactoryMock = $this->createMock(ContactSegmentFilterFactory::class);
        $this->queryFilterHelperMock = $this->createMock(QueryFilterHelper::class);
        $this->evaluateEvent = $this->createMock(ContactFiltersEvaluateEvent::class);

        $this->dynamicContentSubscriber = new DynamicContentSubscriber(
            $this->entityManagerMock,
            $this->segmentFilterFactoryMock,
            $this->queryFilterHelperMock,
            $this->configProviderMock
        );
    }

    public function testOnCampaignBuildWhenPluginDisabled(): void
    {
        $this->configProviderMock->expects($this->once())
            ->method('pluginIsEnabled')
            ->willReturn(false);

        $this->evaluateEvent->expects($this->never())->method('getFilters');

        $this->dynamicContentSubscriber->evaluateFilters($this->evaluateEvent);
    }

    public function testShowPost()
    {
        $client = static::createClient();

        $crawler = $client->request('GET', '/post/hello-world');

        $this->assertGreaterThan(
            0,
            $crawler->filter('html:contains("Hello World")')->count()
        );
    }
}
