<?php

declare(strict_types=1);

namespace MauticPlugin\CustomObjectsBundle\Tests\Unit\EventListener;

use Doctrine\ORM\EntityManager;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Entity\LeadEventLog;
use Mautic\LeadBundle\Entity\LeadEventLogRepository;
use Mautic\LeadBundle\Event\LeadTimelineEvent;
use MauticPlugin\CustomObjectsBundle\Entity\CustomItem;
use MauticPlugin\CustomObjectsBundle\Entity\CustomObject;
use MauticPlugin\CustomObjectsBundle\EventListener\ContactSubscriber;
use MauticPlugin\CustomObjectsBundle\Exception\NotFoundException;
use MauticPlugin\CustomObjectsBundle\Model\CustomItemModel;
use MauticPlugin\CustomObjectsBundle\Provider\ConfigProvider;
use MauticPlugin\CustomObjectsBundle\Provider\CustomItemRouteProvider;
use Symfony\Contracts\Translation\TranslatorInterface;

class ContactSubscriberTest extends \PHPUnit\Framework\TestCase
{
    private $translator;
    private $entityManager;
    private $routeProvider;
    private $customItemModel;
    private $configProvider;
    private $leadTimelineEvent;
    private $leadEventLogRepo;

    /**
     * @var ContactSubscriber
     */
    private $subscriber;

    protected function setUp(): void
    {
        parent::setUp();

        $this->translator        = $this->createMock(TranslatorInterface::class);
        $this->entityManager     = $this->createMock(EntityManager::class);
        $this->routeProvider     = $this->createMock(CustomItemRouteProvider::class);
        $this->customItemModel   = $this->createMock(CustomItemModel::class);
        $this->configProvider    = $this->createMock(ConfigProvider::class);
        $this->leadTimelineEvent = $this->createMock(LeadTimelineEvent::class);
        $this->leadEventLogRepo  = $this->createMock(LeadEventLogRepository::class);
        $this->subscriber        = new ContactSubscriber(
            $this->entityManager,
            $this->translator,
            $this->routeProvider,
            $this->customItemModel,
            $this->configProvider
        );
    }

    public function testPluginDisabled(): void
    {
        $this->configProvider->expects($this->once())
            ->method('pluginIsEnabled')
            ->willReturn(false);

        $this->leadTimelineEvent->expects($this->never())
            ->method('getEventFilters');

        $this->subscriber->onTimelineGenerate($this->leadTimelineEvent);
    }

    public function testPluginEnabledOnPublicPageWhenItemFound(): void
    {
        $this->configProvider->expects($this->once())
            ->method('pluginIsEnabled')
            ->willReturn(true);

        $this->leadTimelineEvent->expects($this->once())
            ->method('getEventFilters');

        $this->translator->expects($this->exactly(3))
            ->method('trans')
            ->withConsecutive(
                ['custom.item.event.linked'],
                ['custom.item.event.unlinked'],
                ['custom.item.unlink.event']
            )
            ->will($this->onConsecutiveCalls(
                'CI Linked',
                'CI Unlinked',
                'CI Unlinked'
            ));

        $this->leadTimelineEvent->expects($this->exactly(2))
            ->method('addEventType')
            ->withConsecutive(
                ['customitem.linked', 'CI Linked'],
                ['customitem.unlinked', 'CI Unlinked']
            );

        $this->leadTimelineEvent->expects($this->exactly(2))
            ->method('isApplicable')
            ->withConsecutive(
                ['customitem.linked'],
                ['customitem.unlinked']
            )->will($this->onConsecutiveCalls(false, true));

        $this->leadTimelineEvent->expects($this->once())
            ->method('getLead')
            ->willReturn(new Lead());

        $this->leadTimelineEvent->expects($this->once())
            ->method('getQueryOptions')
            ->willReturn([]);

        $this->entityManager->expects($this->once())
            ->method('getRepository')
            ->with(LeadEventLog::class)
            ->willReturn($this->leadEventLogRepo);

        $this->leadEventLogRepo->expects($this->once())
            ->method('getEvents')
            ->with(
                $this->isInstanceOf(Lead::class),
                'CustomObject',
                'CustomItem',
                ['unlink'],
                []
            )
            ->willReturn(['results' => [
                [
                    'date_added' => '2019-04-04 12:13:14',
                    'object_id'  => 222,
                    'lead_id'    => 333,
                    'id'         => 444,
                ],
            ]]);

        $customItem   = $this->createMock(CustomItem::class);
        $customObject = $this->createMock(CustomObject::class);

        $this->customItemModel->expects($this->once())
            ->method('fetchEntity')
            ->willReturn($customItem);

        $customObject->expects($this->once())
            ->method('getId')
            ->willReturn(111);

        $customItem->expects($this->once())
            ->method('getId')
            ->willReturn(222);

        $customItem->expects($this->once())
            ->method('getName')
            ->willReturn('Test Item');

        $customItem->expects($this->once())
            ->method('getCustomObject')
            ->willReturn($customObject);

        $this->routeProvider->expects($this->once())
            ->method('buildViewRoute')
            ->with(111, 222)
            ->willReturn('view/route');

        $this->leadTimelineEvent->expects($this->once())
            ->method('addEvent')
            ->with([
                'event'           => 'customitem.unlinked',
                'eventId'         => 'customitem.unlinked.444',
                'eventType'       => 'CI Unlinked',
                'eventLabel'      => ['label' => 'CI Unlinked', 'href' => 'view/route'],
                'timestamp'       => '2019-04-04 12:13:14',
                'icon'            => 'fa-unlink',
                'extra'           => [
                    'date_added' => '2019-04-04 12:13:14',
                    'object_id'  => 222,
                    'lead_id'    => 333,
                    'id'         => 444,
                ],
                'contactId'       => 333,
                '@CustomObjects/SubscribedEvents/Timeline/link.html.twig',
            ]);

        $this->subscriber->onTimelineGenerate($this->leadTimelineEvent);
    }

    public function testPluginEnabledOnPublicPageWhenItemNotFound(): void
    {
        $this->configProvider->expects($this->once())
            ->method('pluginIsEnabled')
            ->willReturn(true);

        $this->leadTimelineEvent->expects($this->once())
            ->method('getEventFilters');

        $this->translator->expects($this->exactly(3))
            ->method('trans')
            ->withConsecutive(
                ['custom.item.event.linked'],
                ['custom.item.link.event.not.found'],
                ['custom.item.event.unlinked']
            )
            ->will($this->onConsecutiveCalls(
                'CI Linked',
                'CI Linked not found',
                'CI Unlinked'
            ));

        $this->leadTimelineEvent->expects($this->exactly(2))
            ->method('addEventType')
            ->withConsecutive(
                ['customitem.linked', 'CI Linked'],
                ['customitem.unlinked', 'CI Unlinked']
            );

        $this->leadTimelineEvent->expects($this->exactly(2))
            ->method('isApplicable')
            ->withConsecutive(
                ['customitem.linked'],
                ['customitem.unlinked']
            )->will($this->onConsecutiveCalls(true, false));

        $this->leadTimelineEvent->expects($this->once())
            ->method('getLead')
            ->willReturn(new Lead());

        $this->leadTimelineEvent->expects($this->once())
            ->method('getQueryOptions')
            ->willReturn([]);

        $this->leadTimelineEvent->expects($this->once())
            ->method('addToCounter')
            ->with('customitem.linked');

        $this->entityManager->expects($this->once())
            ->method('getRepository')
            ->with(LeadEventLog::class)
            ->willReturn($this->leadEventLogRepo);

        $this->leadEventLogRepo->expects($this->once())
            ->method('getEvents')
            ->with(
                $this->isInstanceOf(Lead::class),
                'CustomObject',
                'CustomItem',
                ['link'],
                []
            )
            ->willReturn(['results' => [
                [
                    'date_added' => '2019-04-04 12:13:14',
                    'object_id'  => 222,
                    'lead_id'    => 333,
                    'id'         => 444,
                ],
            ]]);

        $this->customItemModel->expects($this->once())
            ->method('fetchEntity')
            ->will($this->throwException(new NotFoundException()));

        $this->leadTimelineEvent->expects($this->once())
            ->method('addEvent')
            ->with([
                'event'           => 'customitem.linked',
                'eventId'         => 'customitem.linked.444',
                'eventType'       => 'CI Linked',
                'eventLabel'      => 'CI Linked not found',
                'timestamp'       => '2019-04-04 12:13:14',
                'icon'            => 'fa-link',
                'extra'           => [
                    'date_added' => '2019-04-04 12:13:14',
                    'object_id'  => 222,
                    'lead_id'    => 333,
                    'id'         => 444,
                ],
                'contactId'       => 333,
                'contentTemplate' => '@CustomObjects/SubscribedEvents/Timeline/link.html.twig',
            ]);

        $this->subscriber->onTimelineGenerate($this->leadTimelineEvent);
    }
}
