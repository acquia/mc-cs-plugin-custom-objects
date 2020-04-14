<?php

declare(strict_types=1);

/*
 * @copyright   2019 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc
 *
 * @link        https://mautic.com
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\CustomObjectsBundle\Tests\Unit\EventListener;

use Mautic\CoreBundle\Event\CustomContentEvent;
use Mautic\LeadBundle\Entity\Lead;
use MauticPlugin\CustomObjectsBundle\Entity\CustomObject;
use MauticPlugin\CustomObjectsBundle\EventListener\ContactTabSubscriber;
use MauticPlugin\CustomObjectsBundle\Model\CustomObjectModel;
use MauticPlugin\CustomObjectsBundle\Provider\ConfigProvider;
use MauticPlugin\CustomObjectsBundle\Provider\CustomItemRouteProvider;
use MauticPlugin\CustomObjectsBundle\Repository\CustomItemRepository;
use Symfony\Component\Translation\TranslatorInterface;

class ContactTabSubscriberTest extends \PHPUnit\Framework\TestCase
{
    private $customObjectModel;

    private $customItemRepository;

    private $configProvider;

    private $translator;

    private $customItemRouteProvider;

    private $customContentEvent;

    /**
     * @var ContactTabSubscriber
     */
    private $tabSubscriber;

    protected function setUp(): void
    {
        parent::setUp();

        $this->customObjectModel            = $this->createMock(CustomObjectModel::class);
        $this->customItemRepository         = $this->createMock(CustomItemRepository::class);
        $this->configProvider               = $this->createMock(ConfigProvider::class);
        $this->translator                   = $this->createMock(TranslatorInterface::class);
        $this->customItemRouteProvider      = $this->createMock(CustomItemRouteProvider::class);
        $this->customContentEvent           = $this->createMock(CustomContentEvent::class);
        $this->tabSubscriber                = new ContactTabSubscriber(
            $this->customObjectModel,
            $this->customItemRepository,
            $this->configProvider,
            $this->translator,
            $this->customItemRouteProvider
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
        $contact      = $this->createMock(Lead::class);
        $customObject1 = $this->createMock(CustomObject::class);

        $customObject1->expects($this->once())
            ->method('getId')
            ->willReturn(555);

        $customObject1->expects($this->once())
            ->method('getNamePlural')
            ->willReturn('Object A');

        $customObject1->expects($this->once())
            ->method('getType')
            ->willReturn(CustomObject::TYPE_MASTER);

        // Custom objects of type RELATIONSHIP should not get a tab
        $customObject2 = $this->createMock(CustomObject::class);
        $customObject2->method('getType')->willReturn(CustomObject::TYPE_RELATIONSHIP);

        $this->configProvider->expects($this->once())
            ->method('pluginIsEnabled')
            ->willReturn(true);

        $this->customContentEvent->expects($this->at(0))
            ->method('checkContext')
            ->with('MauticLeadBundle:Lead:lead.html.php', 'tabs')
            ->willReturn(true);

        $this->customContentEvent->expects($this->at(1))
            ->method('getVars')
            ->willReturn(['lead' => $contact]);

        $this->customContentEvent->expects($this->at(2))
            ->method('addTemplate')
            ->with(
                'CustomObjectsBundle:SubscribedEvents/Tab:link.html.php',
                [
                    'count' => 13,
                    'title' => 'Object A',
                    'tabId' => 'custom-object-555',
                ]
            );

        $this->customContentEvent->expects($this->at(3))
            ->method('checkContext')
            ->with('MauticLeadBundle:Lead:lead.html.php', 'tabs.content')
            ->willReturn(false);

        $this->customObjectModel->expects($this->once())
            ->method('fetchAllPublishedEntities')
            ->willReturn([$customObject1, $customObject2]);

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

        $this->customContentEvent->expects($this->at(0))
            ->method('checkContext')
            ->with('MauticLeadBundle:Lead:lead.html.php', 'tabs')
            ->willReturn(false);

        $this->customContentEvent->expects($this->at(1))
            ->method('checkContext')
            ->with('MauticLeadBundle:Lead:lead.html.php', 'tabs.content')
            ->willReturn(true);

        $this->customContentEvent->expects($this->at(2))
            ->method('getVars')
            ->willReturn(['lead' => $contact]);

        $this->translator->expects($this->once())
            ->method('trans')
            ->with('custom.item.link.search.placeholder')
            ->willReturn('translated placeholder');

        $this->customItemRouteProvider->expects($this->once())
            ->method('buildLookupRoute')
            ->with(555, 'contact', 45)
            ->willReturn('lookup/route');

        $this->customItemRouteProvider->expects($this->once())
            ->method('buildNewRoute')
            ->with(555)
            ->willReturn('new/route');

        $this->customContentEvent->expects($this->at(3))
            ->method('addTemplate')
            ->with(
                'CustomObjectsBundle:SubscribedEvents/Tab:content.html.php',
                [
                    'page'              => 1,
                    'search'            => '',
                    'customObjectId'    => 555,
                    'currentEntityId'   => 45,
                    'currentEntityType' => 'contact',
                    'tabId'             => 'custom-object-555',
                    'placeholder'       => 'translated placeholder',
                    'lookupRoute'       => 'lookup/route',
                    'newRoute'          => 'new/route',
                ]
            );

        $this->customObjectModel->expects($this->once())
            ->method('fetchAllPublishedEntities')
            ->willReturn([$customObject]);

        $this->tabSubscriber->injectTabs($this->customContentEvent);
    }
}
