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

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\DBAL\Query\QueryBuilder;
use Mautic\CampaignBundle\Entity\Campaign;
use Mautic\CampaignBundle\Event\CampaignEvent;
use Mautic\CampaignBundle\Model\EventModel;
use Mautic\CoreBundle\Event\BuilderEvent;
use Mautic\EmailBundle\Entity\Email;
use Mautic\EmailBundle\Event\EmailSendEvent;
use Mautic\LeadBundle\Entity\LeadList;
use Mautic\LeadBundle\Provider\FilterOperatorProviderInterface;
use Mautic\LeadBundle\Segment\Query\QueryBuilder as SegmentBuilder;
use MauticPlugin\CustomObjectsBundle\CustomFieldType\TextType;
use MauticPlugin\CustomObjectsBundle\DTO\TableConfig;
use MauticPlugin\CustomObjectsBundle\DTO\Token;
use MauticPlugin\CustomObjectsBundle\Entity\CustomField;
use MauticPlugin\CustomObjectsBundle\Entity\CustomFieldValueInterface;
use MauticPlugin\CustomObjectsBundle\Entity\CustomItem;
use MauticPlugin\CustomObjectsBundle\Entity\CustomObject;
use MauticPlugin\CustomObjectsBundle\Event\CustomItemListDbalQueryEvent;
use MauticPlugin\CustomObjectsBundle\EventListener\TokenSubscriber;
use MauticPlugin\CustomObjectsBundle\Exception\InvalidSegmentFilterException;
use MauticPlugin\CustomObjectsBundle\Exception\NotFoundException;
use MauticPlugin\CustomObjectsBundle\Helper\CustomObjectTokenFormatter;
use MauticPlugin\CustomObjectsBundle\Helper\QueryFilterHelper;
use MauticPlugin\CustomObjectsBundle\Helper\TokenParser;
use MauticPlugin\CustomObjectsBundle\Model\CustomItemModel;
use MauticPlugin\CustomObjectsBundle\Model\CustomObjectModel;
use MauticPlugin\CustomObjectsBundle\Provider\ConfigProvider;
use MauticPlugin\CustomObjectsBundle\Segment\Query\Filter\QueryFilterFactory;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\Translation\TranslatorInterface;

class TokenSubscriberTest extends \PHPUnit\Framework\TestCase
{
    private $configProvider;

    private $queryFilterHelper;

    private $queryFilterFactory;

    private $customObjectModel;

    private $customItemModel;

    private $eventModel;

    /**
     * @var TokenParser
     */
    private $tokenParser;

    private $builderEvent;

    private $emailSendEvent;

    private $customItemListDbalQueryEvent;

    /**
     * @var TokenSubscriber
     */
    private $subscriber;

    private $eventDispatcher;

    /**
     * @var CustomObjectTokenFormatter|MockObject
     */
    private $tokenFormatter;

    protected function setUp(): void
    {
        parent::setUp();

        $this->configProvider               = $this->createMock(ConfigProvider::class);
        $this->queryFilterHelper            = $this->createMock(QueryFilterHelper::class);
        $this->queryFilterFactory           = $this->createMock(QueryFilterFactory::class);
        $this->customObjectModel            = $this->createMock(CustomObjectModel::class);
        $this->customItemModel              = $this->createMock(CustomItemModel::class);
        $this->eventModel                   = $this->createMock(EventModel::class);
        $this->tokenParser                  = new TokenParser();
        $this->builderEvent                 = $this->createMock(BuilderEvent::class);
        $this->emailSendEvent               = $this->createMock(EmailSendEvent::class);
        $this->customItemListDbalQueryEvent = $this->createMock(CustomItemListDbalQueryEvent::class);
        $this->eventDispatcher              = $this->createMock(EventDispatcher::class);
        $this->tokenFormatter               = $this->createMock(CustomObjectTokenFormatter::class);
        $this->subscriber                   = new TokenSubscriber(
            $this->configProvider,
            $this->queryFilterHelper,
            $this->queryFilterFactory,
            $this->customObjectModel,
            $this->customItemModel,
            $this->tokenParser,
            $this->eventModel,
            $this->eventDispatcher,
            $this->tokenFormatter
        );
    }

    public function testOnBuilderBuildWhenPluginDisabled(): void
    {
        $this->configProvider->expects($this->once())
            ->method('pluginIsEnabled')
            ->willReturn(false);

        $this->builderEvent->expects($this->never())
            ->method('tokensRequested');

        $this->subscriber->onBuilderBuild($this->builderEvent);
    }

    public function testOnBuilderBuildWhenTokensNotRequested(): void
    {
        $this->configProvider->expects($this->once())
            ->method('pluginIsEnabled')
            ->willReturn(true);

        $this->builderEvent->expects($this->once())
            ->method('tokensRequested')
            ->with(TokenParser::TOKEN)
            ->willReturn(false);

        $this->customObjectModel->expects($this->never())
            ->method('fetchAllPublishedEntities');

        $this->subscriber->onBuilderBuild($this->builderEvent);
    }

    public function testOnBuilderBuild(): void
    {
        $this->configProvider->expects($this->once())
            ->method('pluginIsEnabled')
            ->willReturn(true);

        $this->builderEvent->expects($this->once())
            ->method('tokensRequested')
            ->with(TokenParser::TOKEN)
            ->willReturn(true);

        $customObject = $this->createMock(CustomObject::class);
        $customField  = $this->createMock(CustomField::class);

        $this->customObjectModel->expects($this->once())
            ->method('fetchAllPublishedEntities')
            ->willReturn([$customObject]);

        $customObject->expects($this->once())
            ->method('getCustomFields')
            ->willReturn([$customField]);

        $customObject->method('getAlias')->willReturn('product');
        $customObject->method('getName')->willReturn('Product');
        $customField->method('getAlias')->willReturn('sku');
        $customField->method('getLabel')->willReturn('SKU');

        $this->builderEvent->expects($this->exactly(2))
            ->method('addToken')
            ->withConsecutive(
                [
                    '{custom-object=product:name | where=segment-filter | order=latest | limit=1 | default= | format=default}',
                    'Product: Name',
                ],
                [
                    '{custom-object=product:sku | where=segment-filter | order=latest | limit=1 | default= | format=default}',
                    'Product: SKU',
                ]
            );

        $this->subscriber->onBuilderBuild($this->builderEvent);
    }

    public function testDecodeTokensWhenPluginDisabled(): void
    {
        $this->configProvider->expects($this->once())
            ->method('pluginIsEnabled')
            ->willReturn(false);

        $this->emailSendEvent->expects($this->never())
            ->method('getContent');

        $this->subscriber->decodeTokens($this->emailSendEvent);
    }

    public function testDecodeTokensWithNoTokens(): void
    {
        $html = '<!DOCTYPE html>
        <html>
        <head>
        <title>{subject}</title>
        </head>
        <body>
        Hello, here is the thing:
        Unicorn
        Regards
        </body>
        </html>
        ';
        $email          = new Email();
        $emailSendEvent = new EmailSendEvent(
            null,
            [
                'subject'          => 'CO segment test',
                'content'          => $html,
                'conplainTexttent' => '',
                'email'            => $email,
                'lead'             => ['id' => 2345, 'email' => 'john@doe.email'],
                'source'           => null,
            ]
        );

        $this->configProvider->expects($this->once())
            ->method('pluginIsEnabled')
            ->willReturn(true);

        $this->subscriber->decodeTokens($emailSendEvent);

        $this->assertSame(
            [],
            $emailSendEvent->getTokens()
        );
    }

    public function testDecodeTokensWithWhenCustomObjectNotFound(): void
    {
        $html = '<!DOCTYPE html>
        <html>
        <head>
        <title>{subject}</title>
        </head>
        <body>
        Hello, here is the thing:
        {custom-object=product:sku | where=segment-filter |order=latest|limit=1 | default=No thing} 
        Regards
        </body>
        </html>
        ';
        $email          = new Email();
        $emailSendEvent = new EmailSendEvent(
            null,
            [
                'subject'          => 'CO segment test',
                'content'          => $html,
                'conplainTexttent' => '',
                'email'            => $email,
                'lead'             => ['id' => 2345, 'email' => 'john@doe.email'],
                'source'           => null,
            ]
        );

        $this->configProvider->expects($this->once())
            ->method('pluginIsEnabled')
            ->willReturn(true);

        $this->customObjectModel->expects($this->once())
            ->method('fetchEntityByAlias')
            ->will($this->throwException(new NotFoundException('Custom Object Not Found')));

        $this->subscriber->decodeTokens($emailSendEvent);

        $this->assertSame([], $emailSendEvent->getTokens());
    }

    public function testDecodeTokensWithDefaultValueWhenNoCustomItemFound(): void
    {
        $html = '<!DOCTYPE html>
        <html>
        <head>
        <title>{subject}</title>
        </head>
        <body>
        Hello, here is the thing:
        {custom-object=product:sku | where=segment-filter |order=latest|limit=1 | default=No thing} 
        Regards
        </body>
        </html>
        ';
        $customObject   = $this->createMock(CustomObject::class);
        $email          = new Email();
        $emailSendEvent = new EmailSendEvent(
            null,
            [
                'subject'          => 'CO segment test',
                'content'          => $html,
                'conplainTexttent' => '',
                'email'            => $email,
                'lead'             => ['id' => 2345, 'email' => 'john@doe.email'],
                'source'           => null,
            ]
        );

        $this->configProvider->expects($this->once())
            ->method('pluginIsEnabled')
            ->willReturn(true);

        $this->customObjectModel->expects($this->once())
            ->method('fetchEntityByAlias')
            ->willReturn($customObject);

        $this->subscriber->decodeTokens($emailSendEvent);

        $this->assertSame(
            ['{custom-object=product:sku | where=segment-filter |order=latest|limit=1 | default=No thing}' => 'No thing'],
            $emailSendEvent->getTokens()
        );
    }

    public function testDecodeTokensWithItemName(): void
    {
        $html = '<!DOCTYPE html>
        <html>
        <head>
        <title>{subject}</title>
        </head>
        <body>
        Hello, here is the thing:
        {custom-object=product:name | where=segment-filter |order=latest|limit=1 | default=No thing} 
        Regards
        </body>
        </html>
        ';
        $customObject   = $this->createMock(CustomObject::class);
        $email          = new Email();
        $emailSendEvent = new EmailSendEvent(
            null,
            [
                'subject'          => 'CO segment test',
                'content'          => $html,
                'conplainTexttent' => '',
                'email'            => $email,
                'lead'             => ['id' => 2345, 'email' => 'john@doe.email'],
                'source'           => null,
            ]
        );

        $this->configProvider->expects($this->once())
            ->method('pluginIsEnabled')
            ->willReturn(true);

        $this->customObjectModel->expects($this->once())
            ->method('fetchEntityByAlias')
            ->willReturn($customObject);

        $this->customItemModel->expects($this->once())
            ->method('getArrayTableData')
            ->willReturn([['name' => 'Toaster']]);

        $this->subscriber->decodeTokens($emailSendEvent);

        $this->assertSame(
            ['{custom-object=product:name | where=segment-filter |order=latest|limit=1 | default=No thing}' => 'Toaster'],
            $emailSendEvent->getTokens()
        );
    }

    public function testDecodeTokensWithFoundFieldValue(): void
    {
        $html = '<!DOCTYPE html>
        <html>
        <head>
        <title>{subject}</title>
        </head>
        <body>
        Hello, here is the thing:
        {custom-object=product:sku | where=segment-filter |order=latest|limit=1 | default=No thing} 
        Regards
        </body>
        </html>
        ';
        $segment = new LeadList();
        $segment->setName('CO test');
        $segment->setFilters([
            [
                'glue'     => 'and',
                'field'    => 'cmf_1',
                'object'   => 'custom_object',
                'type'     => 'text',
                'filter'   => '23',
                'display'  => null,
                'operator' => '=',
            ],
            [
                'glue'     => 'and',
                'field'    => 'cmf_10',
                'object'   => 'custom_object',
                'type'     => 'int',
                'filter'   => '4',
                'display'  => null,
                'operator' => '=',
            ],
        ]);
        $email = new Email();
        $email->setName('CO segment test');
        $email->setSubject('CO segment test');
        $email->setCustomHtml($html);
        $email->setEmailType('list');
        $email->setLists([2 => $segment]);
        $emailSendEvent = new EmailSendEvent(
            null,
            [
                'subject'          => 'CO segment test',
                'content'          => $html,
                'conplainTexttent' => '',
                'email'            => $email,
                'lead'             => ['id' => 2345, 'email' => 'john@doe.email'],
                'source'           => null,
            ]
        );

        $customField            = $this->createMock(CustomField::class);
        $customObject           = $this->createMock(CustomObject::class);
        $customItemWithField    = $this->createMock(CustomItem::class);
        $customItemWithoutField = $this->createMock(CustomItem::class);
        $valueEntity            = $this->createMock(CustomFieldValueInterface::class);

        $valueEntity->expects($this->once())
            ->method('getValue')
            ->willReturn('The field value');

        $valueEntity->expects($this->once())
            ->method('getCustomField')
            ->willReturn($customField);

        $customField->expects($this->once())
            ->method('getTypeObject')
            ->willReturn(
                new TextType(
                    $this->createMock(TranslatorInterface::class),
                    $this->createMock(FilterOperatorProviderInterface::class)
                )
            );

        $customObject->method('getId')->willReturn(1234);

        $customItemWithField->expects($this->once())
            ->method('findCustomFieldValueForFieldAlias')
            ->with('sku')
            ->willReturn($valueEntity);

        $customItemWithoutField->expects($this->once())
            ->method('findCustomFieldValueForFieldAlias')
            ->with('sku')
            ->will($this->throwException(new NotFoundException('Field SKU not found')));

        $this->configProvider->expects($this->once())
            ->method('pluginIsEnabled')
            ->willReturn(true);

        $this->customObjectModel->expects($this->once())
            ->method('fetchEntityByAlias')
            ->willReturn($customObject);

        $this->customItemModel->expects($this->once())
            ->method('getArrayTableData')
            ->with($this->callback(function (TableConfig $tableConfig) use ($email) {
                $this->assertSame(1, $tableConfig->getLimit());
                $this->assertSame('CustomItem.date_added', $tableConfig->getOrderBy());
                $this->assertSame('DESC', $tableConfig->getOrderDirection());
                $this->assertSame(0, $tableConfig->getOffset());
                $this->assertSame(1234, $tableConfig->getParameter('customObjectId'));
                $this->assertSame('contact', $tableConfig->getParameter('filterEntityType'));
                $this->assertSame(2345, $tableConfig->getParameter('filterEntityId'));
                $this->assertSame($email, $tableConfig->getParameter('email'));
                $this->assertInstanceOf(Token::class, $tableConfig->getParameter('token'));

                return true;
            }))
            ->willReturn(
                [
                    ['id' => 3456, 'name' => 'Custom Item with sku field'],
                    ['id' => 4567, 'name' => 'Custom Item without sku field'],
                ]
            );

        $this->customItemModel->expects($this->exactly(2))
            ->method('populateCustomFields')
            ->withConsecutive(
                [
                    $this->callback(function (CustomItem $customItem) {
                        $this->assertSame(3456, $customItem->getId());
                        $this->assertSame('Custom Item with sku field', $customItem->getName());

                        return true;
                    }),
                ],
                [
                    $this->callback(function (CustomItem $customItem) {
                        $this->assertSame(4567, $customItem->getId());
                        $this->assertSame('Custom Item without sku field', $customItem->getName());

                        return true;
                    }),
                ]
            )
            ->will($this->onConsecutiveCalls($customItemWithField, $customItemWithoutField));

        $this->subscriber->decodeTokens($emailSendEvent);

        $this->assertSame(
            ['{custom-object=product:sku | where=segment-filter |order=latest|limit=1 | default=No thing}' => 'The field value'],
            $emailSendEvent->getTokens()
        );
    }

    public function testOnListQueryIfNotContactQuery(): void
    {
        $tableConfig  = new TableConfig(10, 1, 'CustomItem.dateAdded', 'DESC');
        $tableConfig->addParameter('customObjectId', 123);
        $tableConfig->addParameter('filterEntityType', 'company');
        $tableConfig->addParameter('filterEntityId', 345);

        $this->customItemListDbalQueryEvent->expects($this->once())
            ->method('getTableConfig')
            ->willReturn($tableConfig);

        $this->customItemListDbalQueryEvent->expects($this->never())
            ->method('getQueryBuilder');

        $this->subscriber->onListQuery($this->customItemListDbalQueryEvent);
    }

    public function testOnListQueryForSegmentFilterWithSegmentEmail(): void
    {
        $segmentBuilder = $this->createMock(SegmentBuilder::class);
        $queryBuilder   = $this->createMock(QueryBuilder::class);
        $token          = $this->tokenParser->findTokens('{custom-object=product:sku | where=segment-filter |order=latest|limit=1 | default=No thing}')->current();
        $segment        = new LeadList();
        $segment->setName('CO test');
        $segment->setFilters([
            [
                'glue'     => 'and',
                'field'    => 'cmf_1',
                'object'   => 'custom_object',
                'type'     => 'text',
                'filter'   => '23',
                'display'  => null,
                'operator' => '=',
            ],
            [
                'glue'     => 'and',
                'field'    => 'cmf_10',
                'object'   => 'custom_object',
                'type'     => 'int',
                'filter'   => '4',
                'display'  => null,
                'operator' => '=',
            ],
        ]);

        $email = $this->createMock(Email::class);
        $email->method('getEmailType')->willReturn('list');
        $email->method('getLists')->willReturn(new ArrayCollection([2 => $segment]));

        $tableConfig  = new TableConfig(10, 1, 'CustomItem.dateAdded', 'DESC');
        $tableConfig->addParameter('customObjectId', 123);
        $tableConfig->addParameter('filterEntityType', 'contact');
        $tableConfig->addParameter('filterEntityId', 345);
        $tableConfig->addParameter('token', $token);
        $tableConfig->addParameter('email', $email);

        $this->customItemListDbalQueryEvent->expects($this->once())
            ->method('getTableConfig')
            ->willReturn($tableConfig);

        $this->customItemListDbalQueryEvent->expects($this->once())
            ->method('getQueryBuilder')
            ->willReturn($queryBuilder);

        $this->queryFilterFactory->expects($this->exactly(2))
            ->method('configureQueryBuilderFromSegmentFilter')
            ->withConsecutive(
                [
                    [
                        'glue'     => 'and',
                        'field'    => 'cmf_1',
                        'object'   => 'custom_object',
                        'type'     => 'text',
                        'filter'   => '23',
                        'display'  => null,
                        'operator' => '=',
                    ],
                    'filter_0',
                ],
                [
                    [
                        'glue'     => 'and',
                        'field'    => 'cmf_10',
                        'object'   => 'custom_object',
                        'type'     => 'int',
                        'filter'   => '4',
                        'display'  => null,
                        'operator' => '=',
                    ],
                    'filter_1',
                ]
            )
            ->will($this->onConsecutiveCalls(
                $segmentBuilder,
                $this->throwException(new InvalidSegmentFilterException('Test invalid filter handling here.'))
            ));

        $segmentBuilder->expects($this->once())
            ->method('select')
            ->with('filter_0_item.id');

        $this->queryFilterHelper->expects($this->once())
            ->method('addContactIdRestriction')
            ->with($segmentBuilder, 'filter_0', 345);

        $segmentBuilder->expects($this->once())
            ->method('getParameters')
            ->willReturn(['queryParam1' => 'queryValue1']);

        $queryBuilder->expects($this->once())
            ->method('setParameter')
            ->with('queryParam1', 'queryValue1');

        $segmentBuilder->expects($this->once())
            ->method('getSQL')
            ->willReturn('SQL QUERY 1');

        $queryBuilder->expects($this->once())
            ->method('innerJoin')
            ->with(
                CustomItem::TABLE_ALIAS,
                '(SQL QUERY 1)',
                'filter_0',
                CustomItem::TABLE_ALIAS.'.id = filter_0.id'
            );

        $this->subscriber->onListQuery($this->customItemListDbalQueryEvent);
    }

    public function testOnListQueryForSegmentFilterWithCampaignEmailWhenEventDoesNotExist(): void
    {
        $queryBuilder  = $this->createMock(QueryBuilder::class);
        $campaignEvent = $this->createMock(CampaignEvent::class);
        $token         = $this->tokenParser->findTokens('{custom-object=product:sku | where=segment-filter |order=latest|limit=1 | default=No thing}')->current();
        $email         = $this->createMock(Email::class);

        $email->method('getEmailType')->willReturn('template');

        $tableConfig  = new TableConfig(10, 1, 'CustomItem.dateAdded', 'DESC');
        $tableConfig->addParameter('customObjectId', 123);
        $tableConfig->addParameter('filterEntityType', 'contact');
        $tableConfig->addParameter('filterEntityId', 345);
        $tableConfig->addParameter('token', $token);
        $tableConfig->addParameter('email', $email);
        $tableConfig->addParameter('source', ['campaign.event', 11]);

        $this->customItemListDbalQueryEvent->expects($this->once())
            ->method('getTableConfig')
            ->willReturn($tableConfig);

        $this->customItemListDbalQueryEvent->expects($this->once())
            ->method('getQueryBuilder')
            ->willReturn($queryBuilder);

        $this->eventModel->expects($this->once())
            ->method('getEntity')
            ->with(11)
            ->willReturn(null);

        $campaignEvent->expects($this->never())
            ->method('getCampaign');

        $this->queryFilterFactory->expects($this->never())
            ->method('configureQueryBuilderFromSegmentFilter');

        $this->subscriber->onListQuery($this->customItemListDbalQueryEvent);
    }

    public function testOnListQueryForSegmentFilterWithCampaignEmailWhenNoSegmentExists(): void
    {
        $queryBuilder  = $this->createMock(QueryBuilder::class);
        $campaignEvent = $this->createMock(CampaignEvent::class);
        $token         = $this->tokenParser->findTokens('{custom-object=product:sku | where=segment-filter |order=latest|limit=1 | default=No thing}')->current();
        $campaign      = $this->createMock(Campaign::class);

        $email = $this->createMock(Email::class);
        $email->method('getEmailType')->willReturn('template');
        $campaign->method('getLists')->willReturn(new ArrayCollection([]));

        $tableConfig  = new TableConfig(10, 1, 'CustomItem.dateAdded', 'DESC');
        $tableConfig->addParameter('customObjectId', 123);
        $tableConfig->addParameter('filterEntityType', 'contact');
        $tableConfig->addParameter('filterEntityId', 345);
        $tableConfig->addParameter('token', $token);
        $tableConfig->addParameter('email', $email);
        $tableConfig->addParameter('source', ['campaign.event', 11]);

        $this->customItemListDbalQueryEvent->expects($this->once())
            ->method('getTableConfig')
            ->willReturn($tableConfig);

        $this->customItemListDbalQueryEvent->expects($this->once())
            ->method('getQueryBuilder')
            ->willReturn($queryBuilder);

        $this->eventModel->expects($this->once())
            ->method('getEntity')
            ->with(11)
            ->willReturn($campaignEvent);

        $campaignEvent->expects($this->once())
            ->method('getCampaign')
            ->willReturn($campaign);

        $this->queryFilterFactory->expects($this->never())
            ->method('configureQueryBuilderFromSegmentFilter');

        $this->subscriber->onListQuery($this->customItemListDbalQueryEvent);
    }

    public function testOnListQueryForSegmentFilterWithCampaignEmail(): void
    {
        $segmentBuilder1 = $this->createMock(SegmentBuilder::class);
        $segmentBuilder2 = $this->createMock(SegmentBuilder::class);
        $queryBuilder    = $this->createMock(QueryBuilder::class);
        $campaign        = $this->createMock(Campaign::class);
        $campaignEvent   = $this->createMock(CampaignEvent::class);
        $token           = $this->tokenParser->findTokens('{custom-object=product:sku | where=segment-filter |order=latest|limit=1 | default=No thing}')->current();
        $segment         = new LeadList();
        $segment->setName('CO test');
        $segment->setFilters([
            [
                'glue'     => 'and',
                'field'    => 'cmf_1',
                'object'   => 'custom_object',
                'type'     => 'text',
                'filter'   => '23',
                'display'  => null,
                'operator' => '=',
            ],
            [
                'glue'     => 'and',
                'field'    => 'cmf_10',
                'object'   => 'custom_object',
                'type'     => 'int',
                'filter'   => '4',
                'display'  => null,
                'operator' => '=',
            ],
        ]);

        $email = $this->createMock(Email::class);
        $email->method('getEmailType')->willReturn('template');
        $campaign->method('getLists')->willReturn(new ArrayCollection([2 => $segment]));

        $tableConfig  = new TableConfig(10, 1, 'CustomItem.dateAdded', 'DESC');
        $tableConfig->addParameter('customObjectId', 123);
        $tableConfig->addParameter('filterEntityType', 'contact');
        $tableConfig->addParameter('filterEntityId', 345);
        $tableConfig->addParameter('token', $token);
        $tableConfig->addParameter('email', $email);
        $tableConfig->addParameter('source', ['campaign.event', 11]);

        $this->customItemListDbalQueryEvent->expects($this->once())
            ->method('getTableConfig')
            ->willReturn($tableConfig);

        $this->customItemListDbalQueryEvent->expects($this->once())
            ->method('getQueryBuilder')
            ->willReturn($queryBuilder);

        $this->eventModel->expects($this->once())
            ->method('getEntity')
            ->with(11)
            ->willReturn($campaignEvent);

        $campaignEvent->expects($this->once())
            ->method('getCampaign')
            ->willReturn($campaign);

        $this->queryFilterFactory->expects($this->exactly(2))
            ->method('configureQueryBuilderFromSegmentFilter')
            ->withConsecutive(
                [
                    [
                        'glue'     => 'and',
                        'field'    => 'cmf_1',
                        'object'   => 'custom_object',
                        'type'     => 'text',
                        'filter'   => '23',
                        'display'  => null,
                        'operator' => '=',
                    ],
                    'filter_0',
                ],
                [
                    [
                        'glue'     => 'and',
                        'field'    => 'cmf_10',
                        'object'   => 'custom_object',
                        'type'     => 'int',
                        'filter'   => '4',
                        'display'  => null,
                        'operator' => '=',
                    ],
                    'filter_1',
                ]
            )
            ->will($this->onConsecutiveCalls(
                $segmentBuilder1,
                $segmentBuilder2
            ));

        $segmentBuilder1->expects($this->once())
            ->method('select')
            ->with('filter_0_item.id');

        $segmentBuilder2->expects($this->once())
            ->method('select')
            ->with('filter_1_item.id');

        $this->queryFilterHelper->expects($this->exactly(2))
            ->method('addContactIdRestriction')
            ->withConsecutive(
                [$segmentBuilder1, 'filter_0', 345],
                [$segmentBuilder2, 'filter_1', 345]
            );

        $segmentBuilder1->expects($this->once())
            ->method('getParameters')
            ->willReturn(['queryParam1' => 'queryValue1']);

        $segmentBuilder2->expects($this->once())
            ->method('getParameters')
            ->willReturn(['queryParam2' => 'queryValue2']);

        $queryBuilder->expects($this->exactly(2))
            ->method('setParameter')
            ->withConsecutive(
                ['queryParam1', 'queryValue1'],
                ['queryParam2', 'queryValue2']
            );

        $segmentBuilder1->expects($this->once())
            ->method('getSQL')
            ->willReturn('SQL QUERY 1');

        $segmentBuilder2->expects($this->once())
            ->method('getSQL')
            ->willReturn('SQL QUERY 2');

        $queryBuilder->expects($this->exactly(2))
            ->method('innerJoin')
            ->withConsecutive(
                [
                    CustomItem::TABLE_ALIAS,
                    '(SQL QUERY 1)',
                    'filter_0',
                    CustomItem::TABLE_ALIAS.'.id = filter_0.id',
                ],
                [
                    CustomItem::TABLE_ALIAS,
                    '(SQL QUERY 2)',
                    'filter_1',
                    CustomItem::TABLE_ALIAS.'.id = filter_1.id',
                ]
            );

        $this->subscriber->onListQuery($this->customItemListDbalQueryEvent);
    }
}
