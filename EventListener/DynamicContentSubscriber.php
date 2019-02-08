<?php
declare(strict_types=1);

/*
 * @copyright   2019 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.inc
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\CustomObjectsBundle\EventListener;

use Doctrine\ORM\EntityManager;
use Mautic\CoreBundle\EventListener\CommonSubscriber;
use Mautic\DynamicContentBundle\DynamicContentEvents;
use Mautic\EmailBundle\Event\ContactFiltersEvaluateEvent;
use Mautic\EmailBundle\EventListener\MatchFilterForLeadTrait;
use Mautic\LeadBundle\Segment\ContactSegmentFilter;
use Mautic\LeadBundle\Segment\ContactSegmentFilterFactory;
use Mautic\LeadBundle\Segment\OperatorOptions;
use Mautic\LeadBundle\Segment\Query\QueryBuilder;
use Mautic\LeadBundle\Segment\RandomParameterName;
use Mautic\PageBundle\Model\TrackableModel;
use Mautic\PageBundle\PageEvents;


use DOMDocument;
use DOMXPath;
use Mautic\AssetBundle\Helper\TokenHelper as AssetTokenHelper;
use Mautic\CoreBundle\Event as MauticEvents;
use Mautic\CoreBundle\Model\AuditLogModel;
use Mautic\DynamicContentBundle\Entity\DynamicContent;
use Mautic\DynamicContentBundle\Helper\DynamicContentHelper;
use Mautic\DynamicContentBundle\Model\DynamicContentModel;
use Mautic\FormBundle\Helper\TokenHelper as FormTokenHelper;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Helper\TokenHelper;
use Mautic\LeadBundle\Model\LeadModel;
use Mautic\PageBundle\Entity\Trackable;
use Mautic\PageBundle\Event\PageDisplayEvent;
use Mautic\PageBundle\Helper\TokenHelper as PageTokenHelper;
use MauticPlugin\CustomObjectsBundle\Helper\QueryFilterHelper;
use MauticPlugin\CustomObjectsBundle\Segment\Query\Filter\CustomFieldFilterQueryBuilder;
use MauticPlugin\MauticFocusBundle\Helper\TokenHelper as FocusTokenHelper;


/**
 * Class DynamicContentSubscriber.
 */
class DynamicContentSubscriber extends CommonSubscriber
{
    use MatchFilterForLeadTrait, QueryFilterHelper;

    /**
     * @var TrackableModel
     */
    protected $trackableModel;

    /**
     * @var PageTokenHelper
     */
    protected $pageTokenHelper;

    /**
     * @var AssetTokenHelper
     */
    protected $assetTokenHelper;

    /**
     * @var FormTokenHelper
     */
    protected $formTokenHelper;

    /**
     * @var FocusTokenHelper
     */
    protected $focusTokenHelper;

    /**
     * @var AuditLogModel
     */
    protected $auditLogModel;

    /**
     * @var LeadModel
     */
    private $leadModel;

    /**
     * @var DynamicContentHelper
     */
    private $dynamicContentHelper;

    /**
     * @var DynamicContentModel
     */
    private $dynamicContentModel;
    /**
     * @var EntityManager
     */
    private $entityManager;

    /**
     * DynamicContentSubscriber constructor.
     *
     * @param TrackableModel       $trackableModel
     * @param PageTokenHelper      $pageTokenHelper
     * @param AssetTokenHelper     $assetTokenHelper
     * @param FormTokenHelper      $formTokenHelper
     * @param FocusTokenHelper     $focusTokenHelper
     * @param AuditLogModel        $auditLogModel
     * @param LeadModel            $leadModel
     * @param DynamicContentHelper $dynamicContentHelper
     * @param DynamicContentModel  $dynamicContentModel
     */
    public function __construct(
        TrackableModel $trackableModel,
        PageTokenHelper $pageTokenHelper,
        AssetTokenHelper $assetTokenHelper,
        FormTokenHelper $formTokenHelper,
        FocusTokenHelper $focusTokenHelper,
        AuditLogModel $auditLogModel,
        LeadModel $leadModel,
        DynamicContentHelper $dynamicContentHelper,
        DynamicContentModel $dynamicContentModel,
        EntityManager $entityManager
    )
    {
        $this->trackableModel       = $trackableModel;
        $this->pageTokenHelper      = $pageTokenHelper;
        $this->assetTokenHelper     = $assetTokenHelper;
        $this->formTokenHelper      = $formTokenHelper;
        $this->focusTokenHelper     = $focusTokenHelper;
        $this->auditLogModel        = $auditLogModel;
        $this->leadModel            = $leadModel;
        $this->dynamicContentHelper = $dynamicContentHelper;
        $this->dynamicContentModel  = $dynamicContentModel;
        $this->entityManager        = $entityManager;
    }

    /**
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return [
            DynamicContentEvents::TOKEN_REPLACEMENT           => ['onTokenReplacement', 0],
            PageEvents::PAGE_ON_DISPLAY                       => ['decodeTokens', 254],
            DynamicContentEvents::ON_CONTACTS_FILTER_EVALUATE => ['evaluateFilters', 0],
        ];
    }

    public function evaluateFilters(ContactFiltersEvaluateEvent $event)
    {
        $eventFilters = $event->getFilters();
        if ($event->isEvaluated()) {
            return;
        }

        foreach ($eventFilters as $eventFilter) {
            if ($eventFilter['object'] != 'custom_object') {
                continue;
            }
            $isCustomFieldValueFilter = preg_match('/^cmf_([0-9]+)$/', $eventFilter['field'], $matches);

            $queryBuilder = new QueryBuilder($this->entityManager->getConnection());
            $queryBuilder->select('*')->from(MAUTIC_TABLE_PREFIX . 'leads', 'l');;


            $operator = OperatorOptions::getFilterExpressionFunctions()[$eventFilter['operator']]['expr'];

            $tableAlias = 'cfq_' . (int) $matches[1] . '';

            if ($isCustomFieldValueFilter) {
                $customQueryBuilder = $this->getCustomValueValueLogicQueryBuilder(
                    $queryBuilder,
                    (int) $matches[1],
                    $eventFilter['glue'],
                    $eventFilter['type'],
                    $operator,
                    $eventFilter['filter'],
                    $tableAlias
                );
            } else {
                throw new \Exception('Not implemented');
            }

            // Restrict to contact ID
            $contact_id_parameter = $tableAlias . '_contact_id';
            $customQueryBuilder->andWhere(
                $customQueryBuilder->expr()->eq($tableAlias . '_contact.contact_id', ":$contact_id_parameter")
            );

            $customQueryBuilder->setParameter($contact_id_parameter, $event->getContact()->getId());

            if ($customQueryBuilder->execute()->rowCount()) {
                $event->setIsEvaluated(true);
                $event->setIsMatched(true);
            } else {
                $event->setIsEvaluated(true);
            }
            $event->stopPropagation();
        }
    }


    /**
     * @param MauticEvents\TokenReplacementEvent $event
     */
    public function onTokenReplacement(MauticEvents\TokenReplacementEvent $event)
    {
        /** @var Lead $lead */
        $lead         = $event->getLead();
        $content      = $event->getContent();
        $clickthrough = $event->getClickthrough();

        if ($content) {
            $tokens = array_merge(
                TokenHelper::findLeadTokens($content, $lead->getProfileFields()),
                $this->pageTokenHelper->findPageTokens($content, $clickthrough),
                $this->assetTokenHelper->findAssetTokens($content, $clickthrough),
                $this->formTokenHelper->findFormTokens($content),
                $this->focusTokenHelper->findFocusTokens($content)
            );

            list($content, $trackables) = $this->trackableModel->parseContentForTrackables(
                $content,
                $tokens,
                'dynamicContent',
                $clickthrough['dynamic_content_id']
            );

            $dwc     = $this->dynamicContentModel->getEntity($clickthrough['dynamic_content_id']);
            $utmTags = [];
            if ($dwc && $dwc instanceof DynamicContent) {
                $utmTags = $dwc->getUtmTags();
            }

            /**
             * @var string
             * @var Trackable $trackable
             */
            foreach ($trackables as $token => $trackable) {
                $tokens[$token] = $this->trackableModel->generateTrackableUrl($trackable, $clickthrough, false, $utmTags);
            }

            $content = str_replace(array_keys($tokens), array_values($tokens), $content);

            $event->setContent($content);
        }
    }

    /**
     * @param PageDisplayEvent $event
     */
    public function decodeTokens(PageDisplayEvent $event)
    {
        $lead = $this->security->isAnonymous() ? $this->leadModel->getCurrentLead() : null;
        if (!$lead) {
            return;
        }

        $content   = $event->getContent();
        $tokens    = $this->dynamicContentHelper->findDwcTokens($content, $lead);
        $leadArray = [];
        if ($lead instanceof Lead) {
            $leadArray = $this->dynamicContentHelper->convertLeadToArray($lead);
        }
        $result = [];
        foreach ($tokens as $token => $dwc) {
            $result[$token] = '';
            if ($this->matchFilterForLead($dwc['filters'], $leadArray)) {
                $result[$token] = $dwc['content'];
            }
        }
        $content = str_replace(array_keys($result), array_values($result), $content);

        // replace slots
        $dom = new DOMDocument('1.0', 'utf-8');
        $dom->loadHTML(mb_convert_encoding($content, 'HTML-ENTITIES', 'UTF-8'), LIBXML_NOERROR);
        $xpath = new DOMXPath($dom);

        $divContent = $xpath->query('//*[@data-slot="dwc"]');
        for ($i = 0; $i < $divContent->length; ++$i) {
            $slot = $divContent->item($i);
            if (!$slotName = $slot->getAttribute('data-param-slot-name')) {
                continue;
            }

            if (!$slotContent = $this->dynamicContentHelper->getDynamicContentForLead($slotName, $lead)) {
                continue;
            }

            $newnode = $dom->createDocumentFragment();
            $newnode->appendXML(mb_convert_encoding($slotContent, 'HTML-ENTITIES', 'UTF-8'));
            $slot->parentNode->replaceChild($newnode, $slot);
        }

        $content = $dom->saveHTML();

        $event->setContent($content);
    }
}
