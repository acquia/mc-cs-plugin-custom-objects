<?php

declare(strict_types=1);

namespace MauticPlugin\CustomObjectsBundle\Tests\Unit\EventListener;

use Mautic\CoreBundle\CoreEvents;
use Mautic\CoreBundle\Event\CustomContentEvent;
use Mautic\LeadBundle\Entity\Lead;
use MauticPlugin\CustomObjectsBundle\Entity\CustomObject;
use MauticPlugin\CustomObjectsBundle\EventListener\ContactTabSubscriber;
use MauticPlugin\CustomObjectsBundle\Model\CustomObjectModel;
use MauticPlugin\CustomObjectsBundle\Provider\ConfigProvider;
use MauticPlugin\CustomObjectsBundle\Provider\CustomItemRouteProvider;
use MauticPlugin\CustomObjectsBundle\Provider\SessionProvider;
use MauticPlugin\CustomObjectsBundle\Provider\SessionProviderFactory;
use MauticPlugin\CustomObjectsBundle\Repository\CustomItemRepository;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\Translation\TranslatorInterface;

class ContactTabSubscriberTest extends TestCase
{
    /**
     * @var MockObject|CustomObjectModel
     */
    private $customObjectModel;

    /**
     * @var MockObject|CustomItemRepository
     */
    private $customItemRepository;

    /**
     * @var MockObject|ConfigProvider
     */
    private $configProvider;

    /**
     * @var MockObject|TranslatorInterface
     */
    private $translator;

    /**
     * @var MockObject|CustomItemRouteProvider
     */
    private $customItemRouteProvider;

    /**
     * @var MockObject|CustomContentEvent
     */
    private $customContentEvent;

    /**
     * @var ContactTabSubscriber
     */
    private $tabSubscriber;

    /**
     * @var SessionProviderFactory|MockObject
     */
    private $sessionProviderFactory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->customObjectModel       = $this->createMock(CustomObjectModel::class);
        $this->customItemRepository    = $this->createMock(CustomItemRepository::class);
        $this->configProvider          = $this->createMock(ConfigProvider::class);
        $this->translator              = $this->createMock(TranslatorInterface::class);
        $this->customItemRouteProvider = $this->createMock(CustomItemRouteProvider::class);
        $this->customContentEvent      = $this->createMock(CustomContentEvent::class);
        $this->sessionProviderFactory  = $this->createMock(SessionProviderFactory::class);
        $this->tabSubscriber           = new ContactTabSubscriber(
            $this->customObjectModel,
            $this->customItemRepository,
            $this->configProvider,
            $this->translator,
            $this->customItemRouteProvider,
            $this->sessionProviderFactory
        );
    }

    public function testPluginDisabled(): void
    {
        $this->configProvider->expects($this->once())
            ->method('pluginIsEnabled')
            ->willReturn(false);

        $this->customContentEvent->expects($this->never())
            ->method('checkContext');

        $this->tabSubscriber->injectTabs($this->customContentEvent);
    }

    public function testForTabsContext(): void
    {
        $contact       = $this->createMock(Lead::class);
        $customObject1 = $this->createMock(CustomObject::class);

        $customObject1->expects($this->once())
            ->method('getId')
            ->willReturn(555);

        $customObject1->expects($this->once())
            ->method('getNamePlural')
            ->willReturn('Object A');

        $customObject1->expects($this->any())
            ->method('getType')
            ->willReturn(CustomObject::TYPE_MASTER);

        // Custom objects of type RELATIONSHIP should not get a tab
        $customObject2 = $this->createMock(CustomObject::class);
        $customObject2->method('getType')->willReturn(CustomObject::TYPE_RELATIONSHIP);

        $this->configProvider->expects($this->once())
            ->method('pluginIsEnabled')
            ->willReturn(true);

        $this->customContentEvent
            ->method('checkContext')
            ->willReturnMap(
                [
                    ['@MauticLead/Lead/lead.html.twig', 'tabs', true],
                    ['@MauticLead/Lead/lead.html.twig', 'tabs.content', false],
                ]
            );

        $this->customContentEvent->expects($this->once())
            ->method('getVars')
            ->willReturn(['lead' => $contact]);

        $this->customContentEvent
            ->method('addTemplate')
            ->withConsecutive(
                [
                    '@CustomObjects/SubscribedEvents/Tab/link.html.twig',
                    [
                        'count' => 13,
                        'title' => 'Object A',
                        'tabId' => 'custom-object-555',
                    ],
                ],
                [
                    '@CustomObjects/SubscribedEvents/Tab/modal.html.twig',
                ]
            );

        $this->customObjectModel->expects($this->once())
            ->method('getMasterCustomObjects')
            ->willReturn([$customObject1]);

        $this->customItemRepository->expects($this->once())
            ->method('countItemsLinkedToContact')
            ->with($customObject1, $contact)
            ->willReturn(13);

        $this->tabSubscriber->injectTabs($this->customContentEvent);
    }

    public function testForTabContentsContext(): void
    {
        $customObject = $this->createMock(CustomObject::class);
        $contact      = $this->createMock(Lead::class);

        $contact->method('getId')->willReturn(45);
        $customObject->method('getId')->willReturn(555);

        $this->configProvider->expects($this->once())
            ->method('pluginIsEnabled')
            ->willReturn(true);

        $this->customContentEvent
            ->method('checkContext')
            ->willReturnMap(
                [
                    ['@MauticLead/Lead/lead.html.twig', 'tabs', false],
                    ['@MauticLead/Lead/lead.html.twig', 'tabs.content', true],
                ]
            );

        $this->customContentEvent->expects($this->once())
            ->method('getVars')
            ->willReturn(['lead' => $contact]);

        $this->translator->expects($this->once())
            ->method('trans')
            ->with('custom.item.link.existing.modal.header.contact')
            ->willReturn('translated modal header');

        $this->customItemRouteProvider->expects($this->once())
            ->method('buildNewRouteWithRedirectToContact')
            ->with(555)
            ->willReturn('new/route');

        $this->customItemRouteProvider->expects($this->exactly(2))
            ->method('buildListRoute')
            ->withConsecutive(
                [555, 1, 'contact', 45],
                [555, 1, 'contact', 45, ['lookup' => 1, 'search' => '']]
            )
            ->willReturnOnConsecutiveCalls(
                'search/route',
                'link/route'
            );

        $sessionProvider = $this->createMock(SessionProvider::class);
        $sessionProvider->method('getFilter')->willReturn('Search something');
        $sessionProvider->method('getNamespace')->willReturn('Some namespace');

        $this->sessionProviderFactory->expects($this->once())
            ->method('createItemProvider')
            ->with(555, 'contact', 45)
            ->willReturn($sessionProvider);

        $this->customContentEvent->expects($this->once())
            ->method('addTemplate')
            ->with(
                '@CustomObjects/SubscribedEvents/Tab/content.html.twig',
                [
                    'customObjectId'       => 555,
                    'currentEntityId'      => 45,
                    'currentEntityType'    => 'contact',
                    'tabId'                => 'custom-object-555',
                    'newRoute'             => 'new/route',
                    'searchId'             => 'list-search-555',
                    'searchValue'          => 'Search something',
                    'searchRoute'          => 'search/route',
                    'linkHeader'           => 'translated modal header',
                    'linkRoute'            => 'link/route',
                    'namespace'            => 'Some namespace',
                ]
            );

        $this->customObjectModel->expects($this->once())
            ->method('fetchAllPublishedEntities')
            ->willReturn([$customObject]);

        $this->tabSubscriber->injectTabs($this->customContentEvent);
    }

    public function testSubscriberEvents()
    {
        $events = ContactTabSubscriber::getSubscribedEvents();
        $this->assertArrayHasKey(CoreEvents::VIEW_INJECT_CUSTOM_CONTENT, $events);
    }
}
