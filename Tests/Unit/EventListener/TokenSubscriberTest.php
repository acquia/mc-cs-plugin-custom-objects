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
use MauticPlugin\CustomObjectsBundle\Model\CustomItemModel;
use Mautic\EmailBundle\Event\EmailSendEvent;
use MauticPlugin\CustomObjectsBundle\EventListener\TokenSubscriber;
use Mautic\CoreBundle\Event\TokenReplacementEvent;
use Mautic\EmailBundle\Entity\Email;
use Mautic\LeadBundle\Entity\LeadList;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\CoreBundle\Event\BuilderEvent;
use MauticPlugin\CustomObjectsBundle\Event\CustomItemListQueryEvent;
use MauticPlugin\CustomObjectsBundle\DTO\TableConfig;
use Doctrine\ORM\QueryBuilder;

class TokenSubscriberTest extends \PHPUnit_Framework_TestCase
{
    private $configProvider;

    private $customObjectModel;

    private $customItemModel;

    private $emailSendEvent;

    private $builderEvent;

    /**
     * @var TokenSubscriber
     */
    private $subscriber;

    protected function setUp(): void
    {
        parent::setUp();

        $this->configProvider    = $this->createMock(ConfigProvider::class);
        $this->customObjectModel = $this->createMock(CustomObjectModel::class);
        $this->customItemModel   = $this->createMock(CustomItemModel::class);
        $this->emailSendEvent    = $this->createMock(EmailSendEvent::class);
        $this->builderEvent      = $this->createMock(BuilderEvent::class);
        $this->subscriber        = new TokenSubscriber(
            $this->configProvider,
            $this->customObjectModel,
            $this->customItemModel
        );
    }

    // public function testDecodeTokensWhenPluginDisabled(): void
    // {
    //     $this->configProvider->expects($this->once())
    //         ->method('pluginIsEnabled')
    //         ->willReturn(false);

    //     $this->emailSendEvent->expects($this->never())
    //         ->method('isDynamicContentParsing');

    //     $this->subscriber->decodeTokens($this->emailSendEvent);
    // }

    public function testDecodeTokens(): void
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

        $this->configProvider->expects($this->once())
            ->method('pluginIsEnabled')
            ->willReturn(true);

        $this->subscriber->decodeTokens($emailSendEvent);
    }

    public function testOnListQuery(): void
    {
        $queryBuilder = $this->createMock(QueryBuilder::class);
        $tableConfig  = new TableConfig(10, 1, 'CustomItem.dateAdded', 'DESC');
        $tableConfig->addParameter('customObjectId', 123);
        $tableConfig->addParameter('filterEntityType', 'contact');
        $tableConfig->addParameter('filterEntityId', 345);
        $tableConfig->addParameter('tokenWhere', [
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

        $event = new CustomItemListQueryEvent($queryBuilder, $tableConfig);

        $this->subscriber->onListQuery($event);
    }

    public function testOnBuilderBuildWhenPluginDisabled(): void
    {
        $this->configProvider->expects($this->once())
            ->method('pluginIsEnabled')
            ->willReturn(false);

        $this->tokenReplacementEvent->expects($this->never())
            ->method('getClickthrough');

        $this->subscriber->onBuilderBuild($this->builderEvent);
    }

    public function testOnBuilderBuild(): void
    {
        $this->configProvider->expects($this->once())
            ->method('pluginIsEnabled')
            ->willReturn(true);

        // $contact = $this->createMock(Lead::class);
        // $clickthrough = [
        //     'tokens' => [],
        //     'lead' => '12',
        //     'dynamicContent' => [[]],
        //     'idHash' => '5d0a1498e2489825662819',
        // ];
        // $event = new TokenReplacementEvent(null, $contact, $clickthrough, new Email());

        // $this->tokenReplacementEvent->expects($this->never())
        //     ->method('getClickthrough');

        $this->subscriber->onBuilderBuild($this->builderEvent);
    }
}
