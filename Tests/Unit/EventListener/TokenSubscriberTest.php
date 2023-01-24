<?php

declare(strict_types=1);

namespace MauticPlugin\CustomObjectsBundle\Tests\Unit\EventListener;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\DBAL\Query\QueryBuilder;
use Mautic\CampaignBundle\Entity\Campaign;
use Mautic\CampaignBundle\Event\CampaignEvent;
use Mautic\CampaignBundle\Model\EventModel;
use Mautic\CoreBundle\Event\BuilderEvent;
use Mautic\EmailBundle\EmailEvents;
use Mautic\EmailBundle\Entity\Email;
use Mautic\EmailBundle\Event\EmailSendEvent;
use Mautic\LeadBundle\Entity\LeadList;
use Mautic\LeadBundle\Provider\FilterOperatorProviderInterface;
use MauticPlugin\CustomObjectsBundle\CustomFieldType\TextType;
use MauticPlugin\CustomObjectsBundle\CustomItemEvents;
use MauticPlugin\CustomObjectsBundle\DTO\TableConfig;
use MauticPlugin\CustomObjectsBundle\DTO\Token;
use MauticPlugin\CustomObjectsBundle\Entity\CustomField;
use MauticPlugin\CustomObjectsBundle\Entity\CustomFieldValueInterface;
use MauticPlugin\CustomObjectsBundle\Entity\CustomItem;
use MauticPlugin\CustomObjectsBundle\Entity\CustomObject;
use MauticPlugin\CustomObjectsBundle\Event\CustomItemListDbalQueryEvent;
use MauticPlugin\CustomObjectsBundle\EventListener\TokenSubscriber;
use MauticPlugin\CustomObjectsBundle\Exception\NotFoundException;
use MauticPlugin\CustomObjectsBundle\Helper\QueryFilterHelper;
use MauticPlugin\CustomObjectsBundle\Helper\TokenFormatter;
use MauticPlugin\CustomObjectsBundle\Helper\TokenParser;
use MauticPlugin\CustomObjectsBundle\Model\CustomItemModel;
use MauticPlugin\CustomObjectsBundle\Model\CustomObjectModel;
use MauticPlugin\CustomObjectsBundle\Provider\ConfigProvider;
use MauticPlugin\CustomObjectsBundle\Segment\Query\Filter\QueryFilterFactory;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Contracts\Translation\TranslatorInterface;

class TokenSubscriberTest extends TestCase
{
    /**
     * @var ConfigProvider|MockObject
     */
    private $configProvider;

    /**
     * @var QueryFilterHelper|MockObject
     */
    private $queryFilterHelper;

    /**
     * @var QueryFilterFactory|MockObject
     */
    private $queryFilterFactory;

    /**
     * @var CustomObjectModel|MockObject
     */
    private $customObjectModel;

    /**
     * @var CustomItemModel|MockObject
     */
    private $customItemModel;

    /**
     * @var TokenParser|MockObject
     */
    private $tokenParser;

    /**
     * @var EventModel|MockObject
     */
    private $eventModel;

    /**
     * @var EventDispatcher|MockObject
     */
    private $eventDispatcher;

    /**
     * @var TokenFormatter|MockObject
     */
    private $tokenFormatter;

    /**
     * @var TokenSubscriber
     */
    private $subscriber;

    /**
     * @var BuilderEvent|MockObject
     */
    private $builderEvent;

    /**
     * @var EmailSendEvent|MockObject
     */
    private $emailSendEvent;

    /**
     * @var CustomItemListDbalQueryEvent|MockObject
     */
    private $customItemListDbalQueryEvent;

    protected function setUp(): void
    {
        parent::setUp();

        $this->configProvider     = $this->createMock(ConfigProvider::class);
        $this->queryFilterHelper  = $this->createMock(QueryFilterHelper::class);
        $this->queryFilterFactory = $this->createMock(QueryFilterFactory::class);
        $this->customObjectModel  = $this->createMock(CustomObjectModel::class);
        $this->customItemModel    = $this->createMock(CustomItemModel::class);
        $this->tokenParser        = $this->createMock(TokenParser::class);
        $this->eventModel         = $this->createMock(EventModel::class);
        $this->eventDispatcher    = $this->createMock(EventDispatcher::class);
        $this->tokenFormatter     = $this->createMock(TokenFormatter::class);
        $this->subscriber         = new TokenSubscriber(
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

        $this->builderEvent                 = $this->createMock(BuilderEvent::class);
        $this->emailSendEvent               = $this->createMock(EmailSendEvent::class);
        $this->customItemListDbalQueryEvent = $this->createMock(CustomItemListDbalQueryEvent::class);
    }

    public function testGetSubscribedEvents(): void
    {
        $this->assertEquals(
            [
                EmailEvents::EMAIL_ON_BUILD                      => ['onBuilderBuild', 0],
                EmailEvents::EMAIL_ON_SEND                       => ['decodeTokens', 0],
                EmailEvents::EMAIL_ON_DISPLAY                    => ['decodeTokens', 0],
                CustomItemEvents::ON_CUSTOM_ITEM_LIST_DBAL_QUERY => ['onListQuery', -1],
            ],
            TokenSubscriber::getSubscribedEvents()
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
        $coAlias = 'coAlias';
        $coName  = 'coName';
        $cfAlias = 'cfAlias';
        $cfLabel = 'cfLabel';

        $this->configProvider->expects($this->once())
            ->method('pluginIsEnabled')
            ->willReturn(true);

        $this->builderEvent->expects($this->once())
            ->method('tokensRequested')
            ->with(TokenParser::TOKEN)
            ->willReturn(true);

        $customObject = $this->createMock(CustomObject::class);
        $customField  = $this->createMock(CustomField::class);

        $customObject->expects($this->once())
            ->method('getCustomFields')
            ->willReturn([$customField]);

        $this->customObjectModel->expects($this->once())
            ->method('fetchAllPublishedEntities')
            ->willReturn([$customObject]);

        // Build data structures for loops
        $customObject->method('getAlias')->willReturn($coAlias);
        $customObject->method('getName')->willReturn($coName);
        $customField->method('getAlias')->willReturn($cfAlias);
        $customField->method('getLabel')->willReturn($cfLabel);

        $this->tokenParser
            ->method('buildTokenWithDefaultOptions')
            ->withConsecutive([$coAlias, 'name'], [$coAlias, $cfAlias])
            ->willReturnOnConsecutiveCalls('token', 'token1');

        $this->tokenParser
            ->method('buildTokenLabel')
            ->withConsecutive([$coName, 'Name'], [$coName, $cfLabel])
            ->willReturn('tokenLabel', 'tokenLabel1');

        $this->builderEvent
            ->method('addToken')
            ->withConsecutive(['token', 'tokenLabel'], ['token1', 'tokenLabel1']);

        $this->builderEvent
            ->method('addToken')
            ->withConsecutive(['token', 'tokenLabel'], ['token1', 'tokenLabel1']);

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
        $this->configProvider->expects($this->once())
            ->method('pluginIsEnabled')
            ->willReturn(true);

        /** @var EmailSendEvent|MockObject $event */
        $event = $this->createMock(EmailSendEvent::class);
        $event->expects($this->once())
            ->method('getContent')
            ->willReturn('eventContent');

        $tokens = $this->createMock(ArrayCollection::class);

        $this->tokenParser->expects($this->once())
            ->method('findTokens')
            ->with('eventContent')
            ->willReturn($tokens);

        $tokens->expects($this->once())
            ->method('count')
            ->willReturn(0);

        $tokens->expects($this->never())
            ->method('map');

        $this->subscriber->decodeTokens($event);
    }

    public function testDecodeTokensWithWhenCustomObjectNotFound(): void
    {
        $coAlias = 'product';

        $this->constructWithDependencies();

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
            ->with($coAlias)
            ->willThrowException(new NotFoundException());

        $this->subscriber->decodeTokens($emailSendEvent);

        $this->assertSame(
            ['{custom-object=product:sku | where=segment-filter |order=latest|limit=1 | default=No thing}' => 'No thing'],
            $emailSendEvent->getTokens()
        );
    }

    public function testDecodeTokensWithDefaultValueWhenNoCustomItemFound(): void
    {
        $this->constructWithDependencies();

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
        $this->constructWithDependencies();

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
        $this->constructWithDependencies();

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

        $valueEntity->expects($this->exactly(2))
            ->method('getValue')
            ->willReturn('The field value');

        $valueEntity->method('getCustomField')
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
                $this->assertSame('CustomItem.id', $tableConfig->getOrderBy());
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

    public function testDecodeTokensWithEmptyFieldValue(): void
    {
        $this->constructWithDependencies();

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

        $customField->expects($this->any())
            ->method('getDefaultValue')
            ->willReturn('The field value');

        $customField->expects($this->any())
            ->method('getTypeObject')
            ->willReturn(
                new TextType(
                    $this->createMock(TranslatorInterface::class),
                    $this->createMock(FilterOperatorProviderInterface::class)
                )
            );

        $valueEntity
            ->method('getValue')
            ->willReturnOnConsecutiveCalls('', 'The field value');

        $valueEntity->expects($this->any())
            ->method('getCustomField')
            ->willReturn($customField);

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
                $this->assertSame('CustomItem.id', $tableConfig->getOrderBy());
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
        $this->constructWithDependencies();

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

    public function testOnListQueryForSegmentFilterWithCampaignEmailWhenEventDoesNotExist(): void
    {
        $this->constructWithDependencies();

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
        $this->constructWithDependencies();

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

    /**
     * This keeps badly constructed subscriber to keep non unit test (behavioral) functionality for cases where it is used.
     */
    private function constructWithDependencies(): void
    {
        $this->configProvider     = $this->createMock(ConfigProvider::class);
        $this->queryFilterHelper  = $this->createMock(QueryFilterHelper::class);
        $this->queryFilterFactory = $this->createMock(QueryFilterFactory::class);
        $this->customObjectModel  = $this->createMock(CustomObjectModel::class);
        $this->customItemModel    = $this->createMock(CustomItemModel::class);
        $this->tokenParser        = new TokenParser();
        $this->eventModel         = $this->createMock(EventModel::class);
        $this->eventDispatcher    = $this->createMock(EventDispatcher::class);
        $this->subscriber         = new TokenSubscriber(
            $this->configProvider,
            $this->queryFilterHelper,
            $this->queryFilterFactory,
            $this->customObjectModel,
            $this->customItemModel,
            $this->tokenParser,
            $this->eventModel,
            $this->eventDispatcher,
            new TokenFormatter()
        );
    }
}
