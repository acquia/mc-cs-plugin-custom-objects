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
use Mautic\CoreBundle\Helper\ArrayHelper;
use MauticPlugin\CustomObjectsBundle\CustomItemEvents;
use MauticPlugin\CustomObjectsBundle\Form\Type\CampaignConditionFieldValueType;
use MauticPlugin\CustomObjectsBundle\Model\CustomFieldModel;
use MauticPlugin\CustomObjectsBundle\Exception\NotFoundException;
use MauticPlugin\CustomObjectsBundle\Provider\ConfigProvider;
use MauticPlugin\CustomObjectsBundle\Model\CustomItemModel;
use MauticPlugin\CustomObjectsBundle\Repository\CustomItemRepository;
use MauticPlugin\CustomObjectsBundle\Segment\Query\Filter\QueryFilterFactory;
use MauticPlugin\CustomObjectsBundle\Helper\QueryFilterHelper;
use MauticPlugin\CustomObjectsBundle\Repository\DbalQueryTrait;

class CampaignSubscriber extends CommonSubscriber
{
    use DbalQueryTrait;

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
     * @var CustomItemRepository
     */
    private $customItemRepository;

    /**
     * @var CustomItemModel
     */
    private $customItemModel;

    /**
     * @var ConfigProvider
     */
    private $configProvider;

    /**
     * @var QueryFilterHelper
     */
    private $queryFilterHelper;

    /**
     * @var QueryFilterFactory
     */
    private $queryFilterFactory;

    /**
     * @param CustomFieldModel     $customFieldModel
     * @param CustomObjectModel    $customObjectModel
     * @param CustomItemRepository $customItemRepository
     * @param CustomItemModel      $customItemModel
     * @param TranslatorInterface  $translator
     * @param ConfigProvider       $configProvider
     * @param QueryFilterHelper    $queryFilterHelper
     * @param QueryFilterFactory           $queryFilterFactory
     */
    public function __construct(
        CustomFieldModel $customFieldModel,
        CustomObjectModel $customObjectModel,
        CustomItemRepository $customItemRepository,
        CustomItemModel $customItemModel,
        TranslatorInterface $translator,
        ConfigProvider $configProvider,
        QueryFilterHelper $queryFilterHelper,
        QueryFilterFactory $queryFilterFactory
    ) {
        $this->customFieldModel     = $customFieldModel;
        $this->customObjectModel    = $customObjectModel;
        $this->customItemRepository = $customItemRepository;
        $this->customItemModel      = $customItemModel;
        $this->translator           = $translator;
        $this->configProvider       = $configProvider;
        $this->queryFilterHelper    = $queryFilterHelper;
        $this->queryFilterFactory   = $queryFilterFactory;
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

        $eventConfig        = $event->getConfig();
        $linkCustomItemId   = (int) ArrayHelper::getValue('linkCustomItemId', $eventConfig);
        $unlinkCustomItemId = (int) ArrayHelper::getValue('unlinkCustomItemId', $eventConfig);
        $contactId          = (int) $event->getLead()->getId();

        if ($linkCustomItemId) {
            try {
                $customItem = $this->customItemModel->fetchEntity($linkCustomItemId);
                $this->customItemModel->linkEntity($customItem, 'contact', $contactId);
            } catch (NotFoundException $e) {
                // Do nothing if the custom item doesn't exist anymore.
            }
        }

        if ($unlinkCustomItemId) {
            try {
                $customItem = $this->customItemModel->fetchEntity($unlinkCustomItemId);
                $this->customItemModel->unlinkEntity($customItem, 'contact', $contactId);
            } catch (NotFoundException $e) {
                // Do nothing if the custom item doesn't exist anymore.
            }
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

        $fakeSegmentFilter = [
            'glue'     => 'and',
            'field'    => 'cmf_'.$customField->getId(),
            'object'   => 'custom_object',
            'type'     => $customField->getType(),
            'filter'   => $event->getConfig()['value'],
            'operator' => $event->getConfig()['operator'],
            'display'  => null,
        ];

        $queryBuilder = $this->queryFilterFactory->configureQueryBuilderFromSegmentFilter(
            $fakeSegmentFilter,
            'queryAlias'
        );

        $this->queryFilterHelper->addContactIdRestriction($queryBuilder, 'queryAlias', (int) $contact->getId());
        $queryBuilder->select('queryAlias_item.id');

        $customItemId = $this->executeSelect($queryBuilder)->fetchColumn();
        dump($event->getConfig()['operator']);
        dump($customField->getTypeObject()->getOperators()[$event->getConfig()['operator']]);
        dump($queryBuilder->getSQL());
        dump($queryBuilder->getParameters());
        dump($customItemId);

        if ($customItemId) {
            $event->setChannel('customItem', $customItemId);
            $event->setResult(true);
        } else {
            $event->setResult(false);
        }

        // $customItemId = $this->customItemRepository->findItemIdForValue(
        //     $customField,
        //     $contact,
        //     $customField->getTypeObject()->getOperators()[$event->getConfig()['operator']]['expr'],
        //     $event->getConfig()['value']
        // );

    }
}
