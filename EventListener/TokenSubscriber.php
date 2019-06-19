<?php

declare(strict_types=1);

/*
 * @copyright   2019 Mautic, Inc. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.com
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\CustomObjectsBundle\EventListener;

use Mautic\CoreBundle\Event\TokenReplacementEvent;
use Mautic\EmailBundle\EmailEvents;
use Mautic\EmailBundle\Entity\Email;
use Mautic\EmailBundle\Event\EmailSendEvent;
use Mautic\LeadBundle\Entity\Lead;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use MauticPlugin\CustomObjectsBundle\Provider\ConfigProvider;
use MauticPlugin\CustomObjectsBundle\Model\CustomObjectModel;
use MauticPlugin\CustomObjectsBundle\Model\CustomItemModel;
use Mautic\EmailBundle\EventListener\MatchFilterForLeadTrait;
use Mautic\CoreBundle\Helper\BuilderTokenHelper;
use Mautic\CoreBundle\Event\BuilderEvent;
use MauticPlugin\CustomObjectsBundle\Entity\CustomObject;
use MauticPlugin\CustomObjectsBundle\Entity\CustomField;

class TokenSubscriber implements EventSubscriberInterface
{
    use MatchFilterForLeadTrait;

    private const TOKEN = '{custom-object=(.*?)}';

    /**
     * @var ConfigProvider
     */
    private $configProvider;

    /**
     * @var CustomObjectModel
     */
    private $customObjectModel;

    /**
     * @var CustomItemModel
     */
    private $customItemModel;

    /**
     * @param ConfigProvider    $configProvider
     * @param CustomObjectModel $customObjectModel
     * @param CustomItemModel   $customItemModel
     */
    public function __construct(
        ConfigProvider $configProvider, 
        CustomObjectModel $customObjectModel, 
        CustomItemModel $customItemModel
    )
    {
        $this->configProvider    = $configProvider;
        $this->customObjectModel = $customObjectModel;
        $this->customItemModel   = $customItemModel;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            EmailEvents::EMAIL_ON_BUILD    => ['onBuilderBuild', 0],
            EmailEvents::EMAIL_ON_SEND     => ['decodeTokens', 0],
            EmailEvents::EMAIL_ON_DISPLAY  => ['decodeTokens', 0],
            EmailEvents::TOKEN_REPLACEMENT => ['onTokenReplacement', 0],
        ];
    }

    /**
     * @param BuilderEvent $event
     */
    public function onBuilderBuild(BuilderEvent $event)
    {
        if (!$this->configProvider->pluginIsEnabled()) {
            return;
        }

        if (!$event->tokensRequested(self::TOKEN)) {
            return;
        }

        $customObjects = $this->customObjectModel->fetchAllPublishedEntities();

        /** @var CustomObject $customObject */
        foreach ($customObjects as $customObject) {
            /** @var CustomField $customField */
            foreach ($customObject->getCustomFields() as $customField) {
                $token = "{custom-object={$customObject->getAlias()}:{$customField->getAlias()} | where=segment-filter | order=latest | limit=1 | default=}";
                $label = "{$customObject->getName()}: {$customField->getLabel()}";
                $event->addToken($token, $label);
            }
        }
    }

    /**
     * @param $content
     * @param $clickthrough
     *
     * @return array
     */
    private function findTokens($content)
    {
        $tokens = [];
        $tokenRegex = self::TOKEN;

        preg_match_all("/{$tokenRegex}/", $content, $matches);
        if (!empty($matches[1])) {
            foreach ($matches[1] as $key => $assetId) {
                $token = $matches[0][$key];

                if (isset($tokens[$token])) {
                    continue;
                }

                $asset          = $this->model->getEntity($assetId);
                $tokens[$token] = ($asset !== null) ? $this->model->generateUrl($asset, true, $clickthrough) : '';
            }
        }

        return $tokens;
    }

    /**
     * @param EmailSendEvent $event
     */
    public function decodeTokens(EmailSendEvent $event)
    {
        if (!$this->configProvider->pluginIsEnabled()) {
            return;
        }

        
    }

    /**
     * @param TokenReplacementEvent $event
     */
    public function onTokenReplacement(TokenReplacementEvent $event)
    {
        if (!$this->configProvider->pluginIsEnabled()) {
            return;
        }

        $clickthrough = $event->getClickthrough();

        if (!array_key_exists('dynamicContent', $clickthrough)) {
            return;
        }

        $lead      = $event->getLead();
        $tokens    = $clickthrough['tokens'];
        $tokenData = $clickthrough['dynamicContent'];

        // if ($lead instanceof Lead) {
        //     $lead = $this->primaryCompanyHelper->getProfileFieldsWithPrimaryCompany($lead);
        // } else {
        //     $lead = $this->primaryCompanyHelper->mergePrimaryCompanyWithProfileFields($lead['id'], $lead);
        // }

        foreach ($tokenData as $data) {
            // Default content
            $filterContent = $data['content'];

            foreach ($data['filters'] as $filter) {
                // if ($this->matchFilterForLead($filter['filters'], $lead)) {
                //     $filterContent = $filter['content'];
                // }
            }

            // Replace lead tokens in dynamic content (but no recurrence on dynamic content to avoid infinite loop)
            $emailSendEvent = new EmailSendEvent(
                null,
                [
                    'content' => $filterContent,
                    'email'   => $event->getPassthrough(),
                    'idHash'  => !empty($clickthrough['idHash']) ? $clickthrough['idHash'] : null,
                    'tokens'  => $tokens,
                    'lead'    => $lead,
                ],
                true
            );

            $this->dispatcher->dispatch(EmailEvents::EMAIL_ON_DISPLAY, $emailSendEvent);
            $untokenizedContent = $emailSendEvent->getContent(true);

            $event->addToken('{dynamiccontent="'.$data['tokenName'].'"}', $untokenizedContent);
        }
    }
}
