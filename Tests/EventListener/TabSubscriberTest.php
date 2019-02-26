<?php declare(strict_types=1);

/*
 * @copyright   2019 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\CustomObjectsBundle\Tests\EventListener;

use MauticPlugin\CustomObjectsBundle\Provider\ConfigProvider;
use MauticPlugin\CustomObjectsBundle\Model\CustomObjectModel;
use MauticPlugin\CustomObjectsBundle\Model\CustomItemModel;
use Mautic\CoreBundle\Event\CustomContentEvent;
use MauticPlugin\CustomObjectsBundle\EventListener\TabSubscriber;
use MauticPlugin\CustomObjectsBundle\Entity\CustomObject;
use Mautic\LeadBundle\Entity\Lead;

class TabSubscriberTest extends \PHPUnit_Framework_TestCase
{
    private $customObjectModel;

    private $customItemModel;

    private $configProvider;

    private $customContentEvent;

    private $tabSubscriber;

    protected function setUp(): void
    {
        parent::setUp();

        $this->customObjectModel  = $this->createMock(CustomObjectModel::class);
        $this->customItemModel    = $this->createMock(CustomItemModel::class);
        $this->configProvider     = $this->createMock(ConfigProvider::class);
        $this->customContentEvent = $this->createMock(CustomContentEvent::class);
        $this->tabSubscriber      = new TabSubscriber(
            $this->customObjectModel,
            $this->customItemModel,
            $this->configProvider
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
        $customObject = $this->createMock(CustomObject::class);
        $contact      = $this->createMock(Lead::class);

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
                    'customObject' => $customObject,
                    'count'        => 13,
                ]
            );

        $this->customContentEvent->expects($this->at(3))
            ->method('checkContext')
            ->with('MauticLeadBundle:Lead:lead.html.php', 'tabs.content')
            ->willReturn(false);

        $this->customObjectModel->expects($this->once())
            ->method('fetchAllPublishedEntities')
            ->willReturn([$customObject]);

        $this->customItemModel->expects($this->once())
            ->method('countItemsLinkedToContact')
            ->with($customObject, $contact)
            ->willReturn(13);

        $this->tabSubscriber->injectTabs($this->customContentEvent);
    }

    public function testForTabContentsContext(): void
    {
        $customObject = $this->createMock(CustomObject::class);
        $contact      = $this->createMock(Lead::class);

        $contact->method('getId')->willReturn(45);

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

        $this->customContentEvent->expects($this->at(3))
            ->method('addTemplate')
            ->with(
                'CustomObjectsBundle:SubscribedEvents/Tab:content.html.php',
                [
                    'customObject' => $customObject,
                    'page'         => 1,
                    'search'       => '',
                    'contactId'    => 45,
                ]
            );

        $this->customObjectModel->expects($this->once())
            ->method('fetchAllPublishedEntities')
            ->willReturn([$customObject]);

        $this->tabSubscriber->injectTabs($this->customContentEvent);
    }
}
