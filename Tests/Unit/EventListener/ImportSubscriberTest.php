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
use MauticPlugin\CustomObjectsBundle\Repository\CustomFieldRepository;
use Symfony\Component\Translation\TranslatorInterface;
use Mautic\LeadBundle\Event\ImportValidateEvent;
use Mautic\LeadBundle\Entity\Import;

class ImportSubscriberTest extends \PHPUnit_Framework_TestCase
{
    private $customObjectModel;

    private $customItemImportModel;

    private $permissionProvider;

    private $configProvider;

    private $customFieldRepository;

    private $translator;

    private $importValidateEvent;

    private $importInitEvent;

    private $importMappingEvent;

    private $importProcessEvent;

    private $import;

    private $customObject;

    /**
     * @var ImportSubscriber
     */
    private $importSubscriber;

    protected function setUp(): void
    {
        parent::setUp();

        $this->customObjectModel     = $this->createMock(CustomObjectModel::class);
        $this->customItemImportModel = $this->createMock(CustomItemImportModel::class);
        $this->permissionProvider    = $this->createMock(CustomItemPermissionProvider::class);
        $this->configProvider        = $this->createMock(ConfigProvider::class);
        $this->customFieldRepository = $this->createMock(CustomFieldRepository::class);
        $this->translator            = $this->createMock(TranslatorInterface::class);
        $this->importValidateEvent   = $this->createMock(ImportValidateEvent::class);
        $this->importInitEvent       = $this->createMock(ImportInitEvent::class);
        $this->importMappingEvent    = $this->createMock(ImportMappingEvent::class);
        $this->importProcessEvent    = $this->createMock(ImportProcessEvent::class);
        $this->import                = $this->createMock(Import::class);
        $this->customObject          = $this->createMock(CustomObject::class);
        $this->importSubscriber      = new ImportSubscriber(
            $this->customObjectModel,
            $this->customItemImportModel,
            $this->configProvider,
            $this->permissionProvider,
            $this->customFieldRepository,
            $this->translator
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
        $this->customObject->method('getNamePlural')->willReturn('Test Object');

        $this->configProvider->expects($this->once())
            ->method('pluginIsEnabled')
            ->willReturn(true);

        $this->importInitEvent->expects($this->exactly(2))
            ->method('getRouteObjectName')
            ->willReturn('custom-object:35');

        $this->customObjectModel->expects($this->once())
            ->method('fetchEntity')
            ->with(35)
            ->willReturn($this->customObject);

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

        $customField = $this->createMock(CustomField::class);

        $customField->expects($this->once())
            ->method('getId')
            ->willReturn(456);

        $customField->expects($this->once())
            ->method('getName')
            ->willReturn('Field A');

        $this->customObject->expects($this->once())
            ->method('getCustomFields')
            ->willReturn(new ArrayCollection([$customField]));

        $this->customObject->expects($this->once())
            ->method('getNamePlural')
            ->willReturn('Object A');

        $this->customObjectModel->expects($this->once())
            ->method('fetchEntity')
            ->with(35)
            ->willReturn($this->customObject);

        $this->importMappingEvent->expects($this->once())
            ->method('setFields')
            ->with([
                'Object A' => [
                    'customItemId'   => 'mautic.core.id',
                    'customItemName' => 'custom.item.name.label',
                    456              => 'Field A',
                ],
                'mautic.lead.special_fields' => [
                    'linkedContactIds' => 'custom.item.link.contact.ids',
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

    public function testOnImportProcessWhenNotACustomObjectImport(): void
    {
        $this->configProvider->expects($this->once())
            ->method('pluginIsEnabled')
            ->willReturn(true);

        $this->importProcessEvent->expects($this->once())
            ->method('getImport')
            ->willReturn($this->import);

        $this->import->expects($this->once())
            ->method('getObject')
            ->willReturn('company');

        $this->permissionProvider->expects($this->never())
            ->method('canCreate');

        $this->importSubscriber->onImportProcess($this->importProcessEvent);
    }

    public function testOnImportProcessWhenCustomObjectNotFound(): void
    {
        $this->configProvider->expects($this->once())
            ->method('pluginIsEnabled')
            ->willReturn(true);

        $this->importProcessEvent->expects($this->once())
            ->method('getImport')
            ->willReturn($this->import);

        $this->import->expects($this->once())
            ->method('getObject')
            ->willReturn('custom-object:350');

        $this->permissionProvider->expects($this->once())
            ->method('canCreate');

        $this->customObjectModel->expects($this->once())
            ->method('fetchEntity')
            ->with(350)
            ->will($this->throwException(new NotFoundException()));

        $this->customItemImportModel->expects($this->never())
            ->method('import');

        $this->importSubscriber->onImportProcess($this->importProcessEvent);
    }

    public function testOnImportProcess(): void
    {
        $this->configProvider->expects($this->once())
            ->method('pluginIsEnabled')
            ->willReturn(true);

        $this->importProcessEvent->expects($this->exactly(2))
            ->method('getImport')
            ->willReturn($this->import);

        $this->importProcessEvent->expects($this->once())
            ->method('getRowData')
            ->willReturn(['some' => 'rows']);

        $this->import->expects($this->once())
            ->method('getObject')
            ->willReturn('custom-object:350');

        $this->permissionProvider->expects($this->once())
            ->method('canCreate');

        $this->customObjectModel->expects($this->once())
            ->method('fetchEntity')
            ->with(350)
            ->willReturn($this->customObject);

        $this->customItemImportModel->expects($this->once())
            ->method('import')
            ->with($this->import, ['some' => 'rows'], $this->customObject)
            ->willReturn(false);

        $this->importProcessEvent->expects($this->once())
            ->method('setWasMerged')
            ->with(false);

        $this->importSubscriber->onImportProcess($this->importProcessEvent);
    }
}
