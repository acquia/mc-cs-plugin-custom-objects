<?php

/*
 * @copyright   2019 Mautic, Inc. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.com
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\CustomObjectsBundle\EventListener;

use Mautic\CoreBundle\EventListener\CommonSubscriber;
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use MauticPlugin\CustomObjectsBundle\CustomObjectsBundle;
use Mautic\LeadBundle\LeadEvents;
use Mautic\CampaignBundle\CampaignEvents;
use Mautic\CampaignBundle\Event\CampaignBuilderEvent;
use Mautic\CampaignBundle\Event\CampaignExecutionEvent;
use MauticPlugin\CustomObjectsBundle\Model\CustomObjectModel;
use Symfony\Component\Translation\TranslatorInterface;
use MauticPlugin\CustomObjectsBundle\Form\Type\CampaignActionLinkType;

class CampaignSubscriber extends CommonSubscriber
{
    /**
     * @var CustomObjectModel
     */
    private $customObjectModel;

    /**
     * @var TranslatorInterface
     */
    protected $translator;

    /**
     *
     * @param CustomObjectModel $customObjectModel
     * @param TranslatorInterface $translator
     */
    public function __construct(
        CustomObjectModel $customObjectModel,
        TranslatorInterface $translator
    )
    {
        $this->customObjectModel = $customObjectModel;
        $this->translator = $translator;
    }

    /**
     * @return array
     */
    public static function getSubscribedEvents(): array
    {
        return [
            CampaignEvents::CAMPAIGN_ON_BUILD      => ['onCampaignBuild'],
            LeadEvents::ON_CAMPAIGN_TRIGGER_ACTION => ['onCampaignTriggerAction'],
        ];
    }

    /**
     * Add event triggers and actions.
     *
     * @param CampaignBuilderEvent $event
     */
    public function onCampaignBuild(CampaignBuilderEvent $event)
    {
        $customObjects = $this->customObjectModel->fetchAllPublishedEntities();

        foreach ($customObjects as $customObject) {
            $event->addAction("custom_item.{$customObject->getId()}.linkcontact", [
                'label'           => $this->translator->trans('custom.item.events.link.contact', ['%customObject%' => $customObject->getNameSingular()]),
                'description'     => $this->translator->trans('custom.item.events.link.contact_descr', ['%customObject%' => $customObject->getNameSingular()]),
                'eventName'       => LeadEvents::ON_CAMPAIGN_TRIGGER_ACTION,
                'formType'        => CampaignActionLinkType::class,
                'formTypeOptions' => ['customObjectId' => $customObject->getId()],
                // 'formTheme'       => 'CustomObjectsBundle:FormTheme\LinkCustomItem',
            ]);
        }
    }

    /**
     * @param CampaignExecutionEvent $event
     */
    public function onCampaignTriggerActionAddToCompany(CampaignExecutionEvent $event)
    {
        if (!$event->checkContext('lead.addtocompany')) {
            return;
        }

        $company           = $event->getConfig()['company'];
        $lead              = $event->getLead();
        $somethingHappened = false;

        if (!empty($company)) {
            $somethingHappened = $this->leadModel->addToCompany($lead, $company);
        }
    }
}
