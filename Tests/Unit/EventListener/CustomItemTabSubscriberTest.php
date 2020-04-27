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
use MauticPlugin\CustomObjectsBundle\Entity\CustomItem;
use MauticPlugin\CustomObjectsBundle\Entity\CustomObject;
use MauticPlugin\CustomObjectsBundle\EventListener\CustomItemTabSubscriber;
use MauticPlugin\CustomObjectsBundle\Model\CustomObjectModel;
use MauticPlugin\CustomObjectsBundle\Provider\CustomItemRouteProvider;
use MauticPlugin\CustomObjectsBundle\Repository\CustomItemRepository;
use Symfony\Component\Translation\TranslatorInterface;

class CustomItemTabSubscriberTest extends \PHPUnit\Framework\TestCase
{
    private $customObjectModel;

    private $customItemRepository;

    private $translator;

    private $customItemRouteProvider;

    private $customContentEvent;

    private $customItem;

    private $customObject;

    /**
     * @var CustomItemTabSubscriber
     */
    private $tabSubscriber;

    protected function setUp(): void
    {
        parent::setUp();

        $this->customObjectModel       = $this->createMock(CustomObjectModel::class);
        $this->customItemRepository    = $this->createMock(CustomItemRepository::class);
        $this->translator              = $this->createMock(TranslatorInterface::class);
        $this->customItemRouteProvider = $this->createMock(CustomItemRouteProvider::class);
        $this->customContentEvent      = $this->createMock(CustomContentEvent::class);
        $this->customObject            = $this->createMock(CustomObject::class);
        $this->customItem              = $this->createMock(CustomItem::class);
        $this->tabSubscriber           = new CustomItemTabSubscriber(
            $this->customObjectModel,
            $this->customItemRepository,
            $this->translator,
            $this->customItemRouteProvider
        );
    }

    public function testForTabsContext(): void
    {
        $itemCustomObject = $this->createMock(CustomObject::class);
        $itemCustomObject->method('getId')->willReturn(444);

        $this->customObject->expects($this->exactly(2))
            ->method('getId')
            ->willReturn(555);

        $this->customItem->expects($this->once())
            ->method('getCustomObject')
            ->willReturn($itemCustomObject);

        $this->customObject->expects($this->once())
            ->method('getNamePlural')
            ->willReturn('Object A');

        $this->customContentEvent->expects($this->at(0))
            ->method('checkContext')
            ->with('CustomObjectsBundle:CustomItem:detail.html.php', 'tabs')
            ->willReturn(true);

        $this->customContentEvent->expects($this->at(1))
            ->method('getVars')
            ->willReturn(['item' => $this->customItem]);

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
            ->with('CustomObjectsBundle:CustomItem:detail.html.php', 'tabs.content')
            ->willReturn(false);

        $this->customObjectModel->expects($this->once())
            ->method('getMasterCustomObjects')
            ->willReturn([$this->customObject]);

        $this->customItemRepository->expects($this->once())
            ->method('countItemsLinkedToAnotherItem')
            ->with($this->customObject, $this->customItem)
            ->willReturn(13);

        $this->tabSubscriber->injectTabs($this->customContentEvent);
    }

    /**
     * We don't want to link custom items of the same type together.
     */
    public function testForTabsContextWhenTheCustomItemMatchesCustomObject(): void
    {
        $this->customObject->expects($this->exactly(2))
            ->method('getId')
            ->willReturn(555);

        $this->customItem->expects($this->once())
            ->method('getCustomObject')
            ->willReturn($this->customObject);

        $this->customContentEvent->expects($this->at(0))
            ->method('checkContext')
            ->with('CustomObjectsBundle:CustomItem:detail.html.php', 'tabs')
            ->willReturn(true);

        $this->customContentEvent->expects($this->at(1))
            ->method('getVars')
            ->willReturn(['item' => $this->customItem]);

        $this->customContentEvent->expects($this->never())
            ->method('addTemplate');

        $this->customContentEvent->expects($this->at(2))
            ->method('checkContext')
            ->with('CustomObjectsBundle:CustomItem:detail.html.php', 'tabs.content')
            ->willReturn(false);

        $this->customObjectModel->expects($this->once())
            ->method('getMasterCustomObjects')
            ->willReturn([$this->customObject]);

        $this->customItemRepository->expects($this->never())
            ->method('countItemsLinkedToAnotherItem');

        $this->tabSubscriber->injectTabs($this->customContentEvent);
    }

    public function testForTabContentsContext(): void
    {
        $this->customItem->method('getId')->willReturn(45);
        $this->customObject->method('getId')->willReturn(555);

        $this->customContentEvent->expects($this->at(0))
            ->method('checkContext')
            ->with('CustomObjectsBundle:CustomItem:detail.html.php', 'tabs')
            ->willReturn(false);

        $this->customContentEvent->expects($this->at(1))
            ->method('checkContext')
            ->with('CustomObjectsBundle:CustomItem:detail.html.php', 'tabs.content')
            ->willReturn(true);

        $this->customContentEvent->expects($this->at(2))
            ->method('getVars')
            ->willReturn(['item' => $this->customItem]);

        $this->translator->expects($this->once())
            ->method('trans')
            ->with('custom.item.link.search.placeholder')
            ->willReturn('translated placeholder');

        $this->customItemRouteProvider->expects($this->once())
            ->method('buildLookupRoute')
            ->with(555, 'customItem', 45)
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
                    'currentEntityType' => 'customItem',
                    'tabId'             => 'custom-object-555',
                    'placeholder'       => 'translated placeholder',
                    'lookupRoute'       => 'lookup/route',
                    'newRoute'          => 'new/route',
                ]
            );

        $this->customObjectModel->expects($this->once())
            ->method('fetchAllPublishedEntities')
            ->willReturn([$this->customObject]);

        $this->tabSubscriber->injectTabs($this->customContentEvent);
    }
}
