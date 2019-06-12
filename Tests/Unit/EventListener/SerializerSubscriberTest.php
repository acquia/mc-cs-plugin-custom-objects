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

use MauticPlugin\CustomObjectsBundle\Provider\ConfigProvider;
use Symfony\Component\HttpFoundation\RequestStack;
use MauticPlugin\CustomObjectsBundle\Model\CustomItemModel;
use MauticPlugin\CustomObjectsBundle\Repository\CustomItemXrefContactRepository;
use MauticPlugin\CustomObjectsBundle\EventListener\SerializerSubscriber;
use JMS\Serializer\EventDispatcher\ObjectEvent;
use Symfony\Component\HttpFoundation\Request;
use Mautic\PageBundle\Entity\Page;
use Mautic\LeadBundle\Entity\Lead;

class SerializerSubscriberTest extends \PHPUnit_Framework_TestCase
{
    private $configProvider;

    private $customItemXrefContactRepository;

    private $customItemModel;

    private $requestStack;

    private $request;

    private $objectEvent;

    /**
     * @var SerializerSubscriber
     */
    private $serializerSubscriber;

    protected function setUp(): void
    {
        parent::setUp();

        $this->configProvider                  = $this->createMock(ConfigProvider::class);
        $this->customItemXrefContactRepository = $this->createMock(CustomItemXrefContactRepository::class);
        $this->customItemModel                 = $this->createMock(CustomItemModel::class);
        $this->requestStack                    = $this->createMock(RequestStack::class);
        $this->objectEvent                     = $this->createMock(ObjectEvent::class);
        $this->request                         = $this->createMock(Request::class);
        $this->serializerSubscriber            = new SerializerSubscriber(
            $this->configProvider,
            $this->customItemXrefContactRepository,
            $this->customItemModel,
            $this->requestStack
        );
    }

    public function testAddCustomItemsIntoContactResponseWithoutAnyReuqest(): void
    {
        $this->requestStack->expects($this->once())
            ->method('getCurrentRequest')
            ->willReturn(null);

        $this->objectEvent->expects($this->never())
            ->method('getObject');

        $this->serializerSubscriber->addCustomItemsIntoContactResponse($this->objectEvent);
    }

    public function testAddCustomItemsIntoContactResponseWithoutIncludeCustomObjectsFlagInTheRequest(): void
    {
        $this->requestStack->expects($this->once())
            ->method('getCurrentRequest')
            ->willReturn($this->request);

        $this->request->expects($this->once())
            ->method('get')
            ->with('includeCustomObjects', false)
            ->willReturn(false);

        $this->objectEvent->expects($this->never())
            ->method('getObject');

        $this->serializerSubscriber->addCustomItemsIntoContactResponse($this->objectEvent);
    }

    public function testAddCustomItemsIntoContactResponseWithNotContactEntity(): void
    {
        $this->requestStack->expects($this->once())
            ->method('getCurrentRequest')
            ->willReturn($this->request);

        $this->request->expects($this->once())
            ->method('get')
            ->with('includeCustomObjects', false)
            ->willReturn(true);

        $this->objectEvent->expects($this->once())
            ->method('getObject')
            ->willReturn(new Page());

        $this->configProvider->expects($this->never())
            ->method('pluginIsEnabled');

        $this->serializerSubscriber->addCustomItemsIntoContactResponse($this->objectEvent);
    }

    public function testAddCustomItemsIntoContactResponseWhenPluginDisabled(): void
    {
        $this->requestStack->expects($this->once())
            ->method('getCurrentRequest')
            ->willReturn($this->request);

        $this->request->expects($this->once())
            ->method('get')
            ->with('includeCustomObjects', false)
            ->willReturn(true);

        $this->objectEvent->expects($this->once())
            ->method('getObject')
            ->willReturn(new Lead());

        $this->configProvider->expects($this->once())
            ->method('pluginIsEnabled')
            ->willReturn(false);

        $this->customItemXrefContactRepository->expects($this->never())
            ->method('getCustomObjectsRelatedToContact');

        $this->serializerSubscriber->addCustomItemsIntoContactResponse($this->objectEvent);
    }

    public function testAddCustomItemsIntoContactResponseWhenNoRelatedObjects(): void
    {
        $this->requestStack->expects($this->once())
            ->method('getCurrentRequest')
            ->willReturn($this->request);

        $this->request->expects($this->once())
            ->method('get')
            ->with('includeCustomObjects', false)
            ->willReturn(true);

        $this->objectEvent->expects($this->once())
            ->method('getObject')
            ->willReturn(new Lead());

        $this->configProvider->expects($this->once())
            ->method('pluginIsEnabled')
            ->willReturn(true);

        $this->customItemXrefContactRepository->expects($this->once())
            ->method('getCustomObjectsRelatedToContact')
            ->willReturn([]);

        $this->serializerSubscriber->addCustomItemsIntoContactResponse($this->objectEvent);
    }
}
