<?php

declare(strict_types=1);

namespace MauticPlugin\CustomObjectsBundle\Tests\Unit\EventListener;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Query\QueryBuilder;
use Doctrine\DBAL\Statement;
use Mautic\CampaignBundle\Event\CampaignBuilderEvent;
use Mautic\CampaignBundle\Event\CampaignExecutionEvent;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Provider\FilterOperatorProviderInterface;
use Mautic\LeadBundle\Segment\Query\QueryBuilder as SegmentQueryBuilder;
use MauticPlugin\CustomObjectsBundle\CustomFieldType\TextType;
use MauticPlugin\CustomObjectsBundle\Entity\CustomField;
use MauticPlugin\CustomObjectsBundle\Entity\CustomItem;
use MauticPlugin\CustomObjectsBundle\Entity\CustomObject;
use MauticPlugin\CustomObjectsBundle\EventListener\CampaignSubscriber;
use MauticPlugin\CustomObjectsBundle\Exception\NotFoundException;
use MauticPlugin\CustomObjectsBundle\Helper\QueryFilterHelper;
use MauticPlugin\CustomObjectsBundle\Model\CustomFieldModel;
use MauticPlugin\CustomObjectsBundle\Model\CustomItemModel;
use MauticPlugin\CustomObjectsBundle\Model\CustomObjectModel;
use MauticPlugin\CustomObjectsBundle\Provider\ConfigProvider;
use MauticPlugin\CustomObjectsBundle\Segment\Query\Filter\QueryFilterFactory;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\Translation\TranslatorInterface;

class CampaignSubscriberTest extends TestCase
{
    private const OBJECT_ID = 63;

    private const FIELD_ID = 42;

    private const CONTACT_ID = 4;

    private $customFieldModel;
    private $customObjectModel;
    private $customItemModel;
    private $translator;
    private $campaignBuilderEvent;
    private $campaignExecutionEvent;
    private $customObject;
    private $customField;
    private $contact;
    private $configProvider;
    private $queryFilterHelper;
    private $queryFilterFactory;
    private $connection;
    private $queryBuilder;
    private $segmentQueryBuilder;
    private $statement;

    /**
     * @var CampaignSubscriber
     */
    private $campaignSubscriber;

    protected function setUp(): void
    {
        parent::setUp();
        defined('MAUTIC_TABLE_PREFIX') || define('MAUTIC_TABLE_PREFIX', '');

        defined('MAUTIC_TABLE_PREFIX') || define('MAUTIC_TABLE_PREFIX', '');

        $this->customFieldModel       = $this->createMock(CustomFieldModel::class);
        $this->customObjectModel      = $this->createMock(CustomObjectModel::class);
        $this->customItemModel        = $this->createMock(CustomItemModel::class);
        $this->translator             = $this->createMock(TranslatorInterface::class);
        $this->configProvider         = $this->createMock(ConfigProvider::class);
        $this->queryFilterHelper      = $this->createMock(QueryFilterHelper::class);
        $this->queryFilterFactory     = $this->createMock(QueryFilterFactory::class);
        $this->connection             = $this->createMock(Connection::class);
        $this->campaignBuilderEvent   = $this->createMock(CampaignBuilderEvent::class);
        $this->campaignExecutionEvent = $this->createMock(CampaignExecutionEvent::class);
        $this->customObject           = $this->createMock(CustomObject::class);
        $this->customField            = $this->createMock(CustomField::class);
        $this->contact                = $this->createMock(Lead::class);
        $this->queryBuilder           = $this->createMock(QueryBuilder::class);
        $this->segmentQueryBuilder    = $this->createMock(SegmentQueryBuilder::class);
        $this->statement              = $this->createMock(Statement::class);
        $this->campaignSubscriber     = new CampaignSubscriber(
            $this->customFieldModel,
            $this->customObjectModel,
            $this->customItemModel,
            $this->translator,
            $this->configProvider,
            $this->queryFilterHelper,
            $this->queryFilterFactory,
            $this->connection
        );

        $this->customObject->method('getId')->willReturn(self::OBJECT_ID);
        $this->customField->method('getId')->willReturn(self::FIELD_ID);
        $this->customField->method('getTypeObject')->willReturn(
            new TextType(
                $this->translator,
                $this->createMock(FilterOperatorProviderInterface::class)
            )
        );
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
        $customFields = new ArrayCollection();
        $customFields->add(new CustomField());

        $this->customObject->expects(self::once())
            ->method('getCustomFields')
            ->willReturn($customFields);

        $this->configProvider->expects($this->once())
            ->method('pluginIsEnabled')
            ->willReturn(true);

        $this->customObjectModel->expects($this->once())
            ->method('fetchAllPublishedEntities')
            ->willReturn([$this->customObject]);

        $this->campaignBuilderEvent
            ->method('addAction')
            ->withConsecutive(['custom_item.63.linkcontact'], ['custom_item.63.fieldvalue']);

        $this->campaignSubscriber->onCampaignBuild($this->campaignBuilderEvent);
    }

    public function testOnCampaignBuildNoCustomFieldsDefined(): void
    {
        $customFields = new ArrayCollection();
        $this->customObject->expects(self::once())
            ->method('getCustomFields')
            ->willReturn($customFields);

        $this->configProvider->expects($this->once())
            ->method('pluginIsEnabled')
            ->willReturn(true);

        $this->customObjectModel->expects($this->once())
            ->method('fetchAllPublishedEntities')
            ->willReturn([$this->customObject]);

        $this->campaignBuilderEvent->expects(self::never())
            ->method('addAction')
            ->with('custom_item.63.linkcontact');

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

        $this->customItemModel->expects($this->never())
            ->method('linkEntity');

        $this->customItemModel->expects($this->never())
            ->method('unlinkEntity');

        $this->campaignSubscriber->onCampaignTriggerAction($this->campaignExecutionEvent);
    }

    public function testOnCampaignTriggerActionWhenItemsNotFound(): void
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

        $this->customItemModel->expects($this->exactly(2))
            ->method('fetchEntity')
            ->withConsecutive([564], [333])
            ->will($this->onConsecutiveCalls(
                $this->throwException(new NotFoundException()),
                $this->throwException(new NotFoundException())
            ));

        $this->customItemModel->expects($this->never())
            ->method('linkEntity');

        $this->customItemModel->expects($this->never())
            ->method('unlinkEntity');

        $this->campaignSubscriber->onCampaignTriggerAction($this->campaignExecutionEvent);
    }

    public function testOnCampaignTriggerAction(): void
    {
        $customItem564 = $this->createMock(CustomItem::class);
        $customItem333 = $this->createMock(CustomItem::class);

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

        $this->customItemModel->expects($this->exactly(2))
            ->method('fetchEntity')
            ->withConsecutive([564], [333])
            ->will($this->onConsecutiveCalls($customItem564, $customItem333));

        $this->customItemModel->expects($this->once())
            ->method('linkEntity')
            ->with($customItem564, 'contact', self::CONTACT_ID);

        $this->customItemModel->expects($this->once())
            ->method('unlinkEntity')
            ->with($customItem333, 'contact', self::CONTACT_ID);

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

    public function testOnCampaignTriggerConditionWhenContactEmpty(): void
    {
        $this->configProvider->expects($this->once())
            ->method('pluginIsEnabled')
            ->willReturn(true);

        $this->campaignExecutionEvent->expects($this->once())
            ->method('getEvent')
            ->willReturn(['type' => 'custom_item.63.fieldvalue']);

        $this->campaignExecutionEvent->expects($this->once())
            ->method('getLead')
            ->willReturn(null);

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

        $this->connection->expects($this->once())
            ->method('createQueryBuilder')
            ->willReturn($this->queryBuilder);

        $this->queryBuilder->expects($this->once())
            ->method('execute')
            ->willReturn($this->statement);

        $this->queryFilterFactory->expects($this->once())
            ->method('configureQueryBuilderFromSegmentFilter')
            ->willReturn($this->segmentQueryBuilder);

        $this->segmentQueryBuilder->expects($this->once())
            ->method('getParameters')
            ->willReturn([]);

        $this->segmentQueryBuilder->expects($this->once())
            ->method('getParameterTypes')
            ->willReturn([]);

        $this->campaignExecutionEvent->expects($this->once())
            ->method('getEvent')
            ->willReturn(['type' => 'custom_item.63.fieldvalue']);

        $this->campaignExecutionEvent->expects($this->once())
            ->method('getLead')
            ->willReturn($this->contact);

        $this->campaignExecutionEvent->method('getConfig')
            ->willReturn([
                'field'    => '432',
                'operator' => 'like',
                'value'    => 'value A',
            ]);

        $this->customFieldModel->expects($this->once())
            ->method('fetchEntity')
            ->willReturn($this->customField);

        $this->statement->expects($this->once())
            ->method('fetchColumn')
            ->willReturn(false);

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

        $this->connection->expects($this->once())
            ->method('createQueryBuilder')
            ->willReturn($this->queryBuilder);

        $this->queryBuilder->expects($this->once())
            ->method('execute')
            ->willReturn($this->statement);

        $this->queryFilterFactory->expects($this->once())
            ->method('configureQueryBuilderFromSegmentFilter')
            ->willReturn($this->segmentQueryBuilder);

        $this->segmentQueryBuilder->expects($this->once())
            ->method('getParameters')
            ->willReturn([]);

        $this->segmentQueryBuilder->expects($this->once())
            ->method('getParameterTypes')
            ->willReturn([]);

        $this->campaignExecutionEvent->expects($this->once())
            ->method('getEvent')
            ->willReturn(['type' => 'custom_item.63.fieldvalue']);

        $this->campaignExecutionEvent->expects($this->once())
            ->method('getLead')
            ->willReturn($this->contact);

        $this->campaignExecutionEvent->method('getConfig')
            ->willReturn([
                'field'    => '432',
                'operator' => 'like',
                'value'    => 'value A',
            ]);

        $this->customFieldModel->expects($this->once())
            ->method('fetchEntity')
            ->willReturn($this->customField);

        $this->campaignExecutionEvent->expects($this->once())
            ->method('setChannel')
            ->with('customItem', 4344);

        $this->statement->expects($this->once())
            ->method('fetchColumn')
            ->willReturn('4344');

        $this->campaignExecutionEvent->expects($this->once())
            ->method('setResult')
            ->with(true);

        $this->campaignSubscriber->onCampaignTriggerCondition($this->campaignExecutionEvent);
    }
}
