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

use Mautic\CoreBundle\EventListener\CommonSubscriber;
use Mautic\CampaignBundle\CampaignEvents;
use Mautic\CampaignBundle\Event\CampaignBuilderEvent;
use Mautic\CampaignBundle\Event\CampaignExecutionEvent;
use MauticPlugin\CustomObjectsBundle\Model\CustomObjectModel;
use Symfony\Component\Translation\TranslatorInterface;
use MauticPlugin\CustomObjectsBundle\Form\Type\CampaignActionLinkType;
use MauticPlugin\CustomObjectsBundle\Model\CustomItemModel;
use Mautic\CoreBundle\Helper\ArrayHelper;
use MauticPlugin\CustomObjectsBundle\CustomItemEvents;
use MauticPlugin\CustomObjectsBundle\Form\Type\CampaignConditionFieldValueType;
use MauticPlugin\CustomObjectsBundle\Model\CustomFieldModel;
use MauticPlugin\CustomObjectsBundle\Exception\NotFoundException;
use MauticPlugin\CustomObjectsBundle\Provider\ConfigProvider;

class CampaignSubscriber extends CommonSubscriber
{
    /**
     * @var TranslatorInterface
     */
    protected $translator;

    /**
     * @var CustomFieldModel
     */
    private $customFieldModel;

    /**
     * @var CustomObjectModel
     */
    private $customObjectModel;

    /**
     * @var CustomItemModel
     */
    private $customItemModel;

    /**
     * @var CustomItemXrefContactModel
     */
    private $customItemXrefContactModel;

    /**
     * @var ConfigProvider
     */
    private $configProvider;

    /**
     * @param CustomFieldModel           $customFieldModel
     * @param CustomObjectModel          $customObjectModel
     * @param CustomItemModel            $customItemModel
     * @param CustomItemXrefContactModel $customItemXrefContactModel
     * @param TranslatorInterface        $translator
     * @param ConfigProvider             $configProvider
     */
    public function __construct(
        CustomFieldModel $customFieldModel,
        CustomObjectModel $customObjectModel,
        CustomItemModel $customItemModel,
        CustomItemXrefContactModel $customItemXrefContactModel,
        TranslatorInterface $translator,
        ConfigProvider $configProvider
    ) {
        $this->customFieldModel           = $customFieldModel;
        $this->customObjectModel          = $customObjectModel;
        $this->customItemModel            = $customItemModel;
        $this->customItemXrefContactModel = $customItemXrefContactModel;
        $this->translator                 = $translator;
        $this->configProvider             = $configProvider;
    }

    /**
     * @return mixed[]
     */
    public static function getSubscribedEvents(): array
    {
        return [
            CampaignEvents::CAMPAIGN_ON_BUILD               => ['onCampaignBuild'],
            CustomItemEvents::ON_CAMPAIGN_TRIGGER_ACTION    => ['onCampaignTriggerAction'],
            CustomItemEvents::ON_CAMPAIGN_TRIGGER_CONDITION => ['onCampaignTriggerCondition'],
        ];
    }

    /**
     * Add event triggers and actions.
     *
     * @param CampaignBuilderEvent $event
     */
    public function onCampaignBuild(CampaignBuilderEvent $event): void
    {
        if (!$this->configProvider->pluginIsEnabled()) {
            return;
        }

        $customObjects = $this->customObjectModel->fetchAllPublishedEntities();

        foreach ($customObjects as $customObject) {
            $event->addAction("custom_item.{$customObject->getId()}.linkcontact", [
                'label'           => $this->translator->trans('custom.item.events.link.contact', ['%customObject%' => $customObject->getNameSingular()]),
                'description'     => $this->translator->trans('custom.item.events.link.contact_descr', ['%customObject%' => $customObject->getNameSingular()]),
                'eventName'       => CustomItemEvents::ON_CAMPAIGN_TRIGGER_ACTION,
                'formType'        => CampaignActionLinkType::class,
                'formTypeOptions' => ['customObjectId' => $customObject->getId()],
            ]);

            $event->addCondition("custom_item.{$customObject->getId()}.fieldvalue", [
                'label'           => $this->translator->trans('custom.item.events.field.value', ['%customObject%' => $customObject->getNameSingular()]),
                'description'     => $this->translator->trans('custom.item.events.field.value_descr', ['%customObject%' => $customObject->getNameSingular()]),
                'eventName'       => CustomItemEvents::ON_CAMPAIGN_TRIGGER_CONDITION,
                'formType'        => CampaignConditionFieldValueType::class,
                'formTheme'       => 'CustomObjectsBundle:FormTheme\FieldValueCondition',
                'formTypeOptions' => ['customObject' => $customObject],
            ]);
        }
    }

    /**
     * @param CampaignExecutionEvent $event
     */
    public function onCampaignTriggerAction(CampaignExecutionEvent $event): void
    {
        if (!$this->configProvider->pluginIsEnabled()) {
            return;
        }

        if (!preg_match('/custom_item.(\d*).linkcontact/', $event->getEvent()['type'])) {
            return;
        }

        $linkCustomItemId   = (int) ArrayHelper::getValue('linkCustomItemId', $event->getConfig());
        $unlinkCustomItemId = (int) ArrayHelper::getValue('unlinkCustomItemId', $event->getConfig());
        $contactId          = (int) $event->getLead()->getId();

        if ($linkCustomItemId) {
            $this->customItemXrefContactModel->linkContact($linkCustomItemId, $contactId);
        }

        if ($unlinkCustomItemId) {
            $this->customItemXrefContactModel->unlinkContact($unlinkCustomItemId, $contactId);
        }
    }

    /**
     * @param CampaignExecutionEvent $event
     */
    public function onCampaignTriggerCondition(CampaignExecutionEvent $event): void
    {
        if (!$this->configProvider->pluginIsEnabled()) {
            return;
        }

        if (!preg_match('/custom_item.(\d*).fieldvalue/', $event->getEvent()['type'])) {
            return;
        }

        $contact = $event->getLead();

        if (empty($contact) || !$contact->getId()) {
            $event->setResult(false);

            return;
        }

        try {
            $customField = $this->customFieldModel->fetchEntity((int) $event->getConfig()['field']);
        } catch (NotFoundException $e) {
            $event->setResult(false);

            return;
        }

        try {
            $customItemId = $this->customItemModel->findItemIdForValue(
                $customField,
                $contact,
                $customField->getTypeObject()->getOperators()[$event->getConfig()['operator']]['expr'],
                $event->getConfig()['value']
            );

            $event->setChannel('customItem', $customItemId);
            $event->setResult(true);
        } catch (NotFoundException $e) {
            $event->setResult(false);
        }
    }
}
