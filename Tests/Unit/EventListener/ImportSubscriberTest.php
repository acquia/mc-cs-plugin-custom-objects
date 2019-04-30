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
use MauticPlugin\CustomObjectsBundle\Model\CustomObjectModel;
use MauticPlugin\CustomObjectsBundle\Model\CustomItemImportModel;
use MauticPlugin\CustomObjectsBundle\EventListener\ImportSubscriber;
use Mautic\LeadBundle\Event\ImportInitEvent;
use Mautic\LeadBundle\Event\ImportMappingEvent;
use Mautic\LeadBundle\Event\ImportProcessEvent;
use MauticPlugin\CustomObjectsBundle\Entity\CustomObject;
use MauticPlugin\CustomObjectsBundle\Provider\CustomItemRouteProvider;
use MauticPlugin\CustomObjectsBundle\Provider\CustomItemPermissionProvider;
use MauticPlugin\CustomObjectsBundle\Exception\NotFoundException;
use MauticPlugin\CustomObjectsBundle\Entity\CustomField;
use Doctrine\Common\Collections\ArrayCollection;

class ImportSubscriberTest extends \PHPUnit_Framework_TestCase
{
    private $customObjectModel;

    private $customItemImportModel;

    private $permissionProvider;

    private $configProvider;

    private $importInitEvent;

    private $importMappingEvent;

    private $importProcessEvent;

    private $importSubscriber;

    protected function setUp(): void
    {
        parent::setUp();

        $this->customObjectModel     = $this->createMock(CustomObjectModel::class);
        $this->customItemImportModel = $this->createMock(CustomItemImportModel::class);
        $this->permissionProvider    = $this->createMock(CustomItemPermissionProvider::class);
        $this->configProvider        = $this->createMock(ConfigProvider::class);
        $this->importInitEvent       = $this->createMock(ImportInitEvent::class);
        $this->importMappingEvent    = $this->createMock(ImportMappingEvent::class);
        $this->importProcessEvent    = $this->createMock(ImportProcessEvent::class);
        $this->importSubscriber      = new ImportSubscriber(
            $this->customObjectModel,
            $this->customItemImportModel,
            $this->configProvider,
            $this->permissionProvider
        );
    }

    public function testOnImportInitWhenPluginIsDisabled(): void
    {
        $this->configProvider->expects($this->once())
            ->method('pluginIsEnabled')
            ->willReturn(false);

        $this->importInitEvent->expects($this->never())
            ->method('getRouteObjectName');

        $this->importSubscriber->onImportInit($this->importInitEvent);
    }

    public function testOnImportInit(): void
    {
        $customObject = $this->createMock(CustomObject::class);

        $customObject->method('getNamePlural')->willReturn('Test Object');

        $this->configProvider->expects($this->once())
            ->method('pluginIsEnabled')
            ->willReturn(true);

        $this->importInitEvent->expects($this->exactly(2))
            ->method('getRouteObjectName')
            ->willReturn('custom-object:35');

        $this->customObjectModel->expects($this->once())
            ->method('fetchEntity')
            ->with(35)
            ->willReturn($customObject);

        $this->importInitEvent->expects($this->once())
            ->method('setObjectIsSupported')
            ->with(true);

        $this->importInitEvent->expects($this->once())
            ->method('setObjectSingular')
            ->with('custom-object:35');

        $this->importInitEvent->expects($this->once())
            ->method('setObjectName')
            ->with('Test Object');

        $this->importInitEvent->expects($this->once())
            ->method('setActiveLink')
            ->with('#mautic_custom_object_35');

        $this->importInitEvent->expects($this->once())
            ->method('setIndexRoute')
            ->with(CustomItemRouteProvider::ROUTE_LIST, ['objectId' => 35]);

        $this->importInitEvent->expects($this->once())
            ->method('stopPropagation');

        $this->importSubscriber->onImportInit($this->importInitEvent);
    }

    public function testOnImportInitWhenCustomObjectNotFound(): void
    {
        $customObject = $this->createMock(CustomObject::class);

        $customObject->method('getNamePlural')->willReturn('Test Object');

        $this->configProvider->expects($this->once())
            ->method('pluginIsEnabled')
            ->willReturn(true);

        $this->importInitEvent->expects($this->once())
            ->method('getRouteObjectName')
            ->willReturn('custom-object:35');

        $this->customObjectModel->expects($this->once())
            ->method('fetchEntity')
            ->with(35)
            ->will($this->throwException(new NotFoundException()));

        $this->importInitEvent->expects($this->never())
            ->method('setObjectIsSupported');

        $this->importInitEvent->expects($this->never())
            ->method('stopPropagation');

        $this->importSubscriber->onImportInit($this->importInitEvent);
    }

    public function testOnFieldMappingWhenPluginIsDisabled(): void
    {
        $this->configProvider->expects($this->once())
            ->method('pluginIsEnabled')
            ->willReturn(false);

        $this->importMappingEvent->expects($this->never())
            ->method('getRouteObjectName');

        $this->importSubscriber->onFieldMapping($this->importMappingEvent);
    }

    public function testOnFieldMappingWhenNotCustomObjectRoute(): void
    {
        $this->configProvider->expects($this->once())
            ->method('pluginIsEnabled')
            ->willReturn(true);

        $this->importMappingEvent->expects($this->once())
            ->method('getRouteObjectName')
            ->willReturn('page');

        $this->customObjectModel->expects($this->never())
            ->method('fetchEntity');

        $this->importSubscriber->onFieldMapping($this->importMappingEvent);
    }

    public function testOnFieldMapping(): void
    {
        $this->configProvider->expects($this->once())
            ->method('pluginIsEnabled')
            ->willReturn(true);

        $this->importMappingEvent->expects($this->once())
            ->method('getRouteObjectName')
            ->willReturn('custom-object:35');

        $customObject = $this->createMock(CustomObject::class);
        $customField  = $this->createMock(CustomField::class);

        $customField->expects($this->once())
            ->method('getId')
            ->willReturn(456);

        $customField->expects($this->once())
            ->method('getName')
            ->willReturn('Field A');

        $customObject->expects($this->once())
            ->method('getCustomFields')
            ->willReturn(new ArrayCollection([$customField]));

        $customObject->expects($this->once())
            ->method('getNamePlural')
            ->willReturn('Object A');

        $this->customObjectModel->expects($this->once())
            ->method('fetchEntity')
            ->with(35)
            ->willReturn($customObject);

        $this->importMappingEvent->expects($this->once())
            ->method('setFields')
            ->with([
                'Object A' => [
                    'customItemId'   => 'mautic.core.id',
                    'customItemName' => 'custom.item.name.label',
                    456              => 'Field A'
                ],
                'mautic.lead.special_fields' => [
                    'linkedContactIds' => 'custom.item.link.contact.ids'
                ],
            ]);

        $this->importSubscriber->onFieldMapping($this->importMappingEvent);
    }

    public function testOnImportProcessWhenPluginIsDisabled(): void
    {
        $this->configProvider->expects($this->once())
            ->method('pluginIsEnabled')
            ->willReturn(false);

        $this->importProcessEvent->expects($this->never())
            ->method('getImport');

        $this->importSubscriber->onImportProcess($this->importProcessEvent);
    }
}
