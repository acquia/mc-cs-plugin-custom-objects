<?php

declare(strict_types=1);

/*
 * @copyright   2019 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\CustomObjectsBundle\Tests\Unit\EventListener;

use MauticPlugin\CustomObjectsBundle\EventListener\CampaignSubscriber;
use Mautic\CampaignBundle\Event\CampaignBuilderEvent;
use MauticPlugin\CustomObjectsBundle\Provider\ConfigProvider;
use Symfony\Component\Translation\TranslatorInterface;
use MauticPlugin\CustomObjectsBundle\Model\CustomObjectModel;
use MauticPlugin\CustomObjectsBundle\Model\CustomFieldModel;
use Mautic\CampaignBundle\Event\CampaignExecutionEvent;
use MauticPlugin\CustomObjectsBundle\Entity\CustomObject;
use Mautic\LeadBundle\Entity\Lead;
use MauticPlugin\CustomObjectsBundle\Exception\NotFoundException;
use MauticPlugin\CustomObjectsBundle\Entity\CustomField;
use MauticPlugin\CustomObjectsBundle\CustomFieldType\TextType;
use MauticPlugin\CustomObjectsBundle\Model\CustomItemXrefContactModel;
use MauticPlugin\CustomObjectsBundle\Repository\CustomItemRepository;

class CampaignSubscriberTest extends \PHPUnit_Framework_TestCase
{
    private const OBJECT_ID = 63;

    private const FIELD_ID = 42;

    private const CONTACT_ID = 4;

    private $customFieldModel;
    private $customObjectModel;
    private $customItemRepository;
    private $customItemXrefContactModel;
    private $translator;
    private $campaignBuilderEvent;
    private $campaignExecutionEvent;
    private $customObject;
    private $customField;
    private $contact;
    private $configProvider;

    /**
     * @var CampaignSubscriber
     */
    private $campaignSubscriber;

    protected function setUp(): void
    {
        parent::setUp();

        $this->customFieldModel           = $this->createMock(CustomFieldModel::class);
        $this->customObjectModel          = $this->createMock(CustomObjectModel::class);
        $this->customItemRepository       = $this->createMock(CustomItemRepository::class);
        $this->customItemXrefContactModel = $this->createMock(CustomItemXrefContactModel::class);
        $this->translator                 = $this->createMock(TranslatorInterface::class);
        $this->configProvider             = $this->createMock(ConfigProvider::class);
        $this->campaignBuilderEvent       = $this->createMock(CampaignBuilderEvent::class);
        $this->campaignExecutionEvent     = $this->createMock(CampaignExecutionEvent::class);
        $this->customObject               = $this->createMock(CustomObject::class);
        $this->customField                = $this->createMock(CustomField::class);
        $this->contact                    = $this->createMock(Lead::class);
        $this->campaignSubscriber         = new CampaignSubscriber(
            $this->customFieldModel,
            $this->customObjectModel,
            $this->customItemRepository,
            $this->customItemXrefContactModel,
            $this->translator,
            $this->configProvider
        );

        $this->customObject->method('getId')->willReturn(self::OBJECT_ID);
        $this->customField->method('getId')->willReturn(self::FIELD_ID);
        $this->customField->method('getTypeObject')->willReturn(new TextType('text'));
        $this->contact->method('getId')->willReturn(self::CONTACT_ID);
    }

    public function testOnCampaignBuildWhenPluginDisabled(): void
    {
        $this->configProvider->expects($this->once())
            ->method('pluginIsEnabled')
            ->willReturn(false);

        $this->customObjectModel->expects($this->never())
            ->method('fetchAllPublishedEntities');

        $this->campaignSubscriber->onCampaignBuild($this->campaignBuilderEvent);
    }

    public function testOnCampaignBuild(): void
    {
        $this->configProvider->expects($this->once())
            ->method('pluginIsEnabled')
            ->willReturn(true);

        $this->customObjectModel->expects($this->once())
            ->method('fetchAllPublishedEntities')
            ->willReturn([$this->customObject]);

        $this->campaignBuilderEvent->expects($this->at(0))
            ->method('addAction')
            ->with('custom_item.63.linkcontact');

        $this->campaignBuilderEvent->expects($this->at(1))
            ->method('addCondition')
            ->with('custom_item.63.fieldvalue');

        $this->campaignSubscriber->onCampaignBuild($this->campaignBuilderEvent);
    }

    public function testOnCampaignTriggerActionWhenPluginDisabled(): void
    {
        $this->configProvider->expects($this->once())
            ->method('pluginIsEnabled')
            ->willReturn(false);

        $this->campaignExecutionEvent->expects($this->never())
            ->method('getEvent');

        $this->campaignSubscriber->onCampaignTriggerAction($this->campaignExecutionEvent);
    }

    public function testOnCampaignTriggerActionForWrongType(): void
    {
        $this->configProvider->expects($this->once())
            ->method('pluginIsEnabled')
            ->willReturn(true);

        $this->campaignExecutionEvent->expects($this->once())
            ->method('getEvent')
            ->willReturn(['type' => 'whatever.action']);

        $this->campaignExecutionEvent->expects($this->never())
            ->method('getConfig');

        $this->campaignSubscriber->onCampaignTriggerAction($this->campaignExecutionEvent);
    }

    public function testOnCampaignTriggerActionWhenNoItemsSelected(): void
    {
        $this->configProvider->expects($this->once())
            ->method('pluginIsEnabled')
            ->willReturn(true);

        $this->campaignExecutionEvent->expects($this->once())
            ->method('getEvent')
            ->willReturn(['type' => 'custom_item.63.linkcontact']);

        $this->campaignExecutionEvent->method('getConfig')
            ->willReturn([]);

        $this->campaignExecutionEvent->expects($this->once())
            ->method('getLead')
            ->willReturn($this->contact);

        $this->customItemXrefContactModel->expects($this->never())
            ->method('linkContact');

        $this->customItemXrefContactModel->expects($this->never())
            ->method('unlinkContact');

        $this->campaignSubscriber->onCampaignTriggerAction($this->campaignExecutionEvent);
    }

    public function testOnCampaignTriggerAction(): void
    {
        $this->configProvider->expects($this->once())
            ->method('pluginIsEnabled')
            ->willReturn(true);

        $this->campaignExecutionEvent->expects($this->once())
            ->method('getEvent')
            ->willReturn(['type' => 'custom_item.63.linkcontact']);

        $this->campaignExecutionEvent->method('getConfig')
            ->willReturn([
                'linkCustomItemId'   => '564',
                'unlinkCustomItemId' => '333',
            ]);

        $this->campaignExecutionEvent->expects($this->once())
            ->method('getLead')
            ->willReturn($this->contact);

        $this->customItemXrefContactModel->expects($this->once())
            ->method('linkContact')
            ->with(564, self::CONTACT_ID);

        $this->customItemXrefContactModel->expects($this->once())
            ->method('unlinkContact')
            ->with(333, self::CONTACT_ID);

        $this->campaignSubscriber->onCampaignTriggerAction($this->campaignExecutionEvent);
    }

    public function testOnCampaignTriggerConditionWhenPluginDisabled(): void
    {
        $this->configProvider->expects($this->once())
            ->method('pluginIsEnabled')
            ->willReturn(false);

        $this->campaignExecutionEvent->expects($this->never())
            ->method('getEvent');

        $this->campaignSubscriber->onCampaignTriggerCondition($this->campaignExecutionEvent);
    }

    public function testOnCampaignTriggerConditionForWrongType(): void
    {
        $this->configProvider->expects($this->once())
            ->method('pluginIsEnabled')
            ->willReturn(true);

        $this->campaignExecutionEvent->expects($this->once())
            ->method('getEvent')
            ->willReturn(['type' => 'whatever.action']);

        $this->campaignExecutionEvent->expects($this->never())
            ->method('getConfig');

        $this->campaignSubscriber->onCampaignTriggerCondition($this->campaignExecutionEvent);
    }

    public function testOnCampaignTriggerConditionWhenFieldNotFound(): void
    {
        $this->configProvider->expects($this->once())
            ->method('pluginIsEnabled')
            ->willReturn(true);

        $this->campaignExecutionEvent->expects($this->once())
            ->method('getEvent')
            ->willReturn(['type' => 'custom_item.63.fieldvalue']);

        $this->campaignExecutionEvent->expects($this->once())
            ->method('getLead')
            ->willReturn($this->contact);

        $this->campaignExecutionEvent->expects($this->once())
            ->method('getConfig')
            ->willReturn(['field' => '432']);

        $this->customFieldModel->expects($this->once())
            ->method('fetchEntity')
            ->will($this->throwException(new NotFoundException()));

        $this->campaignExecutionEvent->expects($this->once())
            ->method('setResult')
            ->with(false);

        $this->campaignSubscriber->onCampaignTriggerCondition($this->campaignExecutionEvent);
    }

    public function testOnCampaignTriggerConditionWhenItemNotFound(): void
    {
        $this->configProvider->expects($this->once())
            ->method('pluginIsEnabled')
            ->willReturn(true);

        $this->campaignExecutionEvent->expects($this->once())
            ->method('getEvent')
            ->willReturn(['type' => 'custom_item.63.fieldvalue']);

        $this->campaignExecutionEvent->expects($this->once())
            ->method('getLead')
            ->willReturn($this->contact);

        $this->campaignExecutionEvent->expects($this->exactly(3))
            ->method('getConfig')
            ->willReturn([
                'field'    => '432',
                'operator' => 'like',
                'value'    => 'value A',
            ]);

        $this->customFieldModel->expects($this->once())
            ->method('fetchEntity')
            ->willReturn($this->customField);

        $this->customItemRepository->expects($this->once())
            ->method('findItemIdForValue')
            ->with($this->customField, $this->contact, 'like', 'value A')
            ->will($this->throwException(new NotFoundException()));

        $this->campaignExecutionEvent->expects($this->once())
            ->method('setResult')
            ->with(false);

        $this->campaignSubscriber->onCampaignTriggerCondition($this->campaignExecutionEvent);
    }

    public function testOnCampaignTriggerCondition(): void
    {
        $this->configProvider->expects($this->once())
            ->method('pluginIsEnabled')
            ->willReturn(true);

        $this->campaignExecutionEvent->expects($this->once())
            ->method('getEvent')
            ->willReturn(['type' => 'custom_item.63.fieldvalue']);

        $this->campaignExecutionEvent->expects($this->once())
            ->method('getLead')
            ->willReturn($this->contact);

        $this->campaignExecutionEvent->expects($this->exactly(3))
            ->method('getConfig')
            ->willReturn([
                'field'    => '432',
                'operator' => 'like',
                'value'    => 'value A',
            ]);

        $this->customFieldModel->expects($this->once())
            ->method('fetchEntity')
            ->willReturn($this->customField);

        $this->customItemRepository->expects($this->once())
            ->method('findItemIdForValue')
            ->with($this->customField, $this->contact, 'like', 'value A')
            ->willReturn(4344);

        $this->campaignExecutionEvent->expects($this->once())
            ->method('setChannel')
            ->with('customItem', 4344);

        $this->campaignExecutionEvent->expects($this->once())
            ->method('setResult')
            ->with(true);

        $this->campaignSubscriber->onCampaignTriggerCondition($this->campaignExecutionEvent);
    }
}
