<?php

declare(strict_types=1);

namespace MauticPlugin\CustomObjectsBundle\Tests\Unit\EventListener;

use Doctrine\Common\Collections\ArrayCollection;
use Mautic\LeadBundle\Entity\Import;
use Mautic\LeadBundle\Entity\LeadEventLog;
use Mautic\LeadBundle\Event\ImportInitEvent;
use Mautic\LeadBundle\Event\ImportMappingEvent;
use Mautic\LeadBundle\Event\ImportProcessEvent;
use Mautic\LeadBundle\Event\ImportValidateEvent;
use MauticPlugin\CustomObjectsBundle\Entity\CustomField;
use MauticPlugin\CustomObjectsBundle\Entity\CustomObject;
use MauticPlugin\CustomObjectsBundle\EventListener\ImportSubscriber;
use MauticPlugin\CustomObjectsBundle\Exception\NotFoundException;
use MauticPlugin\CustomObjectsBundle\Model\CustomItemImportModel;
use MauticPlugin\CustomObjectsBundle\Model\CustomObjectModel;
use MauticPlugin\CustomObjectsBundle\Provider\ConfigProvider;
use MauticPlugin\CustomObjectsBundle\Provider\CustomItemPermissionProvider;
use MauticPlugin\CustomObjectsBundle\Repository\CustomFieldRepository;
use Symfony\Component\Form\Form;
use Symfony\Contracts\Translation\TranslatorInterface;

class ImportSubscriberTest extends \PHPUnit\Framework\TestCase
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

    private $form;

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
        $this->importInitEvent       = new ImportInitEvent('unicorn');
        $this->importMappingEvent    = new ImportMappingEvent('unicorn');
        $this->import                = $this->createMock(Import::class);
        $leadEventLogMock            = $this->createMock(LeadEventLog::class);
        $this->importProcessEvent    = new ImportProcessEvent($this->import, $leadEventLogMock, []);
        $this->customObject          = $this->createMock(CustomObject::class);
        $this->form                  = $this->createMock(Form::class);
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

        $this->importSubscriber->onImportInit($this->importInitEvent);
    }

    public function testOnImportInit(): void
    {
        $this->customObject->method('getNamePlural')->willReturn('Test Object');

        $this->configProvider->expects($this->once())
            ->method('pluginIsEnabled')
            ->willReturn(true);

        $this->importInitEvent->routeObjectName = 'custom-object:35';

        $this->customObjectModel->expects($this->once())
            ->method('fetchEntity')
            ->with(35)
            ->willReturn($this->customObject);

        $this->importInitEvent->objectSupported = true;

        $this->importInitEvent->objectSingular = 'custom-object:35';

        $this->importInitEvent->objectName = 'Test Object';

        $this->importInitEvent->activeLink = '#mautic_custom_object_35';

        $this->importSubscriber->onImportInit($this->importInitEvent);
    }

    public function testOnImportInitWhenCustomObjectNotFound(): void
    {
        $this->configProvider->expects($this->once())
            ->method('pluginIsEnabled')
            ->willReturn(true);

        $this->importInitEvent->routeObjectName = 'custom-object:35';

        $this->customObjectModel->expects($this->once())
            ->method('fetchEntity')
            ->with(35)
            ->will($this->throwException(new NotFoundException()));

        $this->importSubscriber->onImportInit($this->importInitEvent);
    }

    public function testOnFieldMappingWhenPluginIsDisabled(): void
    {
        $this->configProvider->expects($this->once())
            ->method('pluginIsEnabled')
            ->willReturn(false);

        $this->importSubscriber->onFieldMapping($this->importMappingEvent);
    }

    public function testOnFieldMappingWhenNotCustomObjectRoute(): void
    {
        $this->configProvider->expects($this->once())
            ->method('pluginIsEnabled')
            ->willReturn(true);

        $this->importMappingEvent->routeObjectName = 'page';

        $this->customObjectModel->expects($this->never())
            ->method('fetchEntity');

        $this->importSubscriber->onFieldMapping($this->importMappingEvent);
    }

    public function testOnFieldMapping(): void
    {
        $this->configProvider->expects($this->once())
            ->method('pluginIsEnabled')
            ->willReturn(true);

        $this->importMappingEvent->routeObjectName = 'custom-object:35';

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

        $this->importMappingEvent->fields = [
            'Object A' => [
                'customItemId'   => 'mautic.core.id',
                'customItemName' => 'custom.item.name.label',
                456              => 'Field A',
            ],
            'mautic.lead.special_fields' => [
                'linkedContactIds' => 'custom.item.link.contact.ids',
            ],
        ];

        $this->importSubscriber->onFieldMapping($this->importMappingEvent);
    }

    public function testOnValidateImportWhenPluginIsDisabled(): void
    {
        $this->configProvider->expects($this->once())
            ->method('pluginIsEnabled')
            ->willReturn(false);

        $this->importValidateEvent->expects($this->never())
            ->method('getRouteObjectName');

        $this->importSubscriber->onValidateImport($this->importValidateEvent);
    }

    public function testOnValidateImportWhenNotCustomObjectImport(): void
    {
        $this->configProvider->expects($this->once())
            ->method('pluginIsEnabled')
            ->willReturn(true);

        $this->importValidateEvent->expects($this->once())
            ->method('getRouteObjectName')
            ->willReturn('lead');

        $this->importValidateEvent->expects($this->never())
            ->method('getForm');

        $this->importSubscriber->onValidateImport($this->importValidateEvent);
    }

    public function testOnValidateImportWhenNoFieldsWereMatched(): void
    {
        $this->configProvider->expects($this->once())
            ->method('pluginIsEnabled')
            ->willReturn(true);

        $this->importValidateEvent->expects($this->once())
            ->method('getRouteObjectName')
            ->willReturn('custom-object:1000');

        $this->importValidateEvent->expects($this->once())
            ->method('getForm')
            ->willReturn($this->form);

        $this->form->expects($this->once())
            ->method('getData')
            ->willReturn([]);

        $this->form->expects($this->once())
            ->method('addError');

        $this->translator->expects($this->once())
            ->method('trans')
            ->with('mautic.lead.import.matchfields', [], 'validators');

        $this->importSubscriber->onValidateImport($this->importValidateEvent);
    }

    public function testOnValidateImportWhenSomeRequiredFieldsWereNotMatched(): void
    {
        $customField = $this->createMock(CustomField::class);

        $this->configProvider->expects($this->once())
            ->method('pluginIsEnabled')
            ->willReturn(true);

        $this->importValidateEvent->expects($this->once())
            ->method('getRouteObjectName')
            ->willReturn('custom-object:1000');

        $this->importValidateEvent->expects($this->once())
            ->method('getForm')
            ->willReturn($this->form);

        $this->form->expects($this->once())
            ->method('getData')
            ->willReturn(['alias-1' => '45']); // 45 is custom field ID.

        $this->customFieldRepository->expects($this->once())
            ->method('getRequiredCustomFieldsForCustomObject')
            ->with(1000)
            ->willReturn(new ArrayCollection([$customField]));

        $customField->expects($this->exactly(2))
            ->method('getAlias')
            ->willReturn('alias-2');

        $customField->expects($this->once())
            ->method('getLabel')
            ->willReturn('Label 2');

        $this->form->expects($this->once())
            ->method('addError');

        $this->translator->expects($this->exactly(2))
            ->method('trans')
            ->withConsecutive(
                [
                    'custom.item.name.label',
                ],
                [
                    'mautic.import.missing.required.fields',
                    [
                        '%requiredFields%' => 'Label 2 (alias-2), Name',
                        '%fieldOrFields%'  => 'fields',
                    ],
                    'validators',
                ]
            )
            ->will($this->onConsecutiveCalls('Name', 'These fields are required...'));

        $this->importSubscriber->onValidateImport($this->importValidateEvent);
    }

    public function testOnValidateImportWhenAllRequiredFieldsAreMatched(): void
    {
        $customField = $this->createMock(CustomField::class);

        $this->configProvider->expects($this->once())
            ->method('pluginIsEnabled')
            ->willReturn(true);

        $this->importValidateEvent->expects($this->once())
            ->method('getRouteObjectName')
            ->willReturn('custom-object:1000');

        $this->importValidateEvent->expects($this->once())
            ->method('getForm')
            ->willReturn($this->form);

        $this->form->expects($this->once())
            ->method('getData')
            ->willReturn(['alias-1' => '45', 'name' => 'customItemName']); // 45 is custom field ID.

        $this->customFieldRepository->expects($this->once())
            ->method('getRequiredCustomFieldsForCustomObject')
            ->with(1000)
            ->willReturn(new ArrayCollection([$customField]));

        $customField->expects($this->once())
            ->method('getAlias')
            ->willReturn('alias-1');

        $this->form->expects($this->never())
            ->method('addError');

        $this->importValidateEvent->expects($this->once())
            ->method('setMatchedFields')
            ->with([
                'alias-1' => '45',
                'name'    => 'customItemName',
            ]);

        $this->importSubscriber->onValidateImport($this->importValidateEvent);
    }

    public function testOnImportProcessWhenPluginIsDisabled(): void
    {
        $this->configProvider->expects($this->once())
            ->method('pluginIsEnabled')
            ->willReturn(false);

        $this->importSubscriber->onImportProcess($this->importProcessEvent);
    }

    public function testOnImportProcessWhenNotACustomObjectImport(): void
    {
        $this->configProvider->expects($this->once())
            ->method('pluginIsEnabled')
            ->willReturn(true);

        $this->importProcessEvent->import = $this->import;

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

        $this->importProcessEvent->import = $this->import;

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

        $this->importProcessEvent->import = $this->import;

        $this->importProcessEvent->rowData = ['some' => 'rows'];

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

        $this->importProcessEvent->setWasMerged(false);

        $this->importSubscriber->onImportProcess($this->importProcessEvent);
    }
}
