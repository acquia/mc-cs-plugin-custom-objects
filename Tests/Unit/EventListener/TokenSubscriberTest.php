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
use Doctrine\ORM\PersistentCollection;
use Mautic\LeadBundle\Entity\LeadList;
use Mautic\LeadBundle\Entity\Lead;
use Doctrine\Common\Collections\ArrayCollection;

class TokenSubscriberTest extends \PHPUnit_Framework_TestCase
{
    private $configProvider;

    private $customObjectModel;

    private $customItemModel;

    private $emailSendEvent;

    private $tokenReplacementEvent;

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
        $this->tokenReplacementEvent = $this->createMock(TokenReplacementEvent::class);
        $this->subscriber     = new TokenSubscriber(
            $this->configProvider,
            $this->customObjectModel,
            $this->customItemModel
        );
    }

    public function testDecodeTokensWhenPluginDisabled(): void
    {
        $this->configProvider->expects($this->once())
            ->method('pluginIsEnabled')
            ->willReturn(false);

        $this->emailSendEvent->expects($this->never())
            ->method('isDynamicContentParsing');

        $this->subscriber->decodeTokens($this->emailSendEvent);
    }

    public function testDecodeTokens(): void
    {
        $html = '<!DOCTYPE html><html xmlns="http://www.w3.org/1999/xhtml" style="" class=" js flexbox flexboxlegacy canvas canvastext webgl no-touch geolocation postmessage websqldatabase indexeddb hashchange history draganddrop websockets rgba hsla multiplebgs backgroundsize borderimage borderradius boxshadow textshadow opacity cssanimations csscolumns cssgradients cssreflections csstransforms csstransforms3d csstransitions fontface generatedcontent video audio localstorage sessionstorage webworkers no-applicationcache svg inlinesvg smil svgclippaths js csstransforms csstransforms3d csstransitions responsejs "><head>
        <title>{subject}</title>
        <meta name="viewport" content="width=device-width, initial-scale=1.0" />
        <style type="text/css" media="only screen and (max-width: 480px)">
            /* Mobile styles */
            @media only screen and (max-width: 480px) {
                [class="w320"] {
                    width: 320px !important;
                }
                [class="mobile-block"] {';
        $contact = $this->createMock(Lead::class);
        $segment = new LeadList();
        $segment->setName('CO test');
        $segment->setFilters([
            [
                'glue' => 'and',
                'field' => 'cmf_1',
                'object' => 'custom_object',
                'type' => 'text',
                'filter' => '23',
                'display' => null,
                'operator' => '=',
            ],
            [
                'glue' => 'and',
                'field' => 'cmf_10',
                'object' => 'custom_object',
                'type' => 'int',
                'filter' => '4',
                'display' => null,
                'operator' => '=',
            ],
        ]);
        $email = new Email();
        $email->setName('CO segment test');
        $email->setSubject('CO segment test');
        $email->setCustomHtml($html);
        $email->setEmailType('list');
        $email->setLists([2 => $segment]);
        $event = new EmailSendEvent(
            null,
            [
                'subject' => 'CO segment test',
                'content' => 'Default Dynamic Content',
                'conplainTexttent' => '',
                'email' => $email,
                'lead' => $contact,
                'source' => null
            ]
        );
        $this->configProvider->expects($this->once())
            ->method('pluginIsEnabled')
            ->willReturn(false);

        $this->emailSendEvent->expects($this->never())
            ->method('isDynamicContentParsing');

        $this->subscriber->decodeTokens($this->emailSendEvent);
    }

    public function testOnTokenReplacementWhenPluginDisabled(): void
    {
        $this->configProvider->expects($this->once())
            ->method('pluginIsEnabled')
            ->willReturn(false);

        $this->tokenReplacementEvent->expects($this->never())
            ->method('getClickthrough');

        $this->subscriber->onTokenReplacement($this->tokenReplacementEvent);
    }

    public function testOnTokenReplacement(): void
    {
        $this->configProvider->expects($this->once())
            ->method('pluginIsEnabled')
            ->willReturn(true);
    


        $contact = $this->createMock(Lead::class);
        $clickthrough = [
            'tokens' => [],
            'lead' => '12',
            'dynamicContent' => [[]],
            'idHash' => '5d0a1498e2489825662819',
        ];
        $event = new TokenReplacementEvent(null, $contact, $clickthrough, new Email());

        $this->tokenReplacementEvent->expects($this->never())
            ->method('getClickthrough');

        $this->subscriber->onTokenReplacement($this->tokenReplacementEvent);
    }
}
