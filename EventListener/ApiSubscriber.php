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
use MauticPlugin\CustomObjectsBundle\Provider\ConfigProvider;
use MauticPlugin\CustomObjectsBundle\Model\CustomObjectModel;
use Mautic\ApiBundle\ApiEvents;
use Mautic\ApiBundle\Event\ApiEntityEvent;
use MauticPlugin\CustomObjectsBundle\Model\CustomItemModel;
use MauticPlugin\CustomObjectsBundle\Exception\NotFoundException;
use MauticPlugin\CustomObjectsBundle\Entity\CustomItem;
use Mautic\LeadBundle\Entity\Lead;

class ApiSubscriber extends CommonSubscriber
{
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
    ) {
        $this->configProvider    = $configProvider;
        $this->customObjectModel = $customObjectModel;
        $this->customItemModel   = $customItemModel;
    }

    /**
     * @return mixed[]
     */
    public static function getSubscribedEvents(): array
    {
        return [
            ApiEvents::API_ON_ENTITY_POST_SAVE => 'onEntityPostSave',
        ];
    }

    /**
     * @param ApiEntityEvent $event
     */
    public function onEntityPostSave(ApiEntityEvent $event): void
    {
        $request = $event->getRequest();

        if (!$this->configProvider->pluginIsEnabled() || !'/api/contacts/new' === $request->getPathInfo() || !$request->isMethod('POST') || !$request->request->has('customObjects')) {
            return;
        }

        $customObjects = $request->request->get('customObjects');

        if (!is_array($customObjects)) {
            return;
        }

        /** @var Lead $contact */
        $contact = $event->getEntity();

        foreach ($customObjects as $customObjectAlias => $customItems) {
            $customObject = $this->customObjectModel->fetchEntity((int) $customObjectAlias); // @todo change this to fetch by alias.

            foreach ($customItems as $customItemData) {
                if (empty($customItemData['id'])) {
                    $customItem = new CustomItem($customObject);
                    unset($customItemData['id']);
                } else {
                    $customItem = $this->customItemModel->fetchEntity((int) $customItemData['id']);
                }

                if (!(empty($customItemData['name']))) {
                    $customItem->setName($customItemData['name']);
                    unset($customItemData['name']);
                }

                foreach ($customItemData as $fieldAlias => $value) {
                    try {
                        $customFieldValue = $customItem->findCustomFieldValueForFieldId((int) $fieldAlias); // @todo change this to field alias instead of id.
                    } catch (NotFoundException $e) {
                        $customFieldValue = $customItem->createNewCustomFieldValueByFieldId((int) $fieldAlias, $value); // @todo change this to field alias instead of id.
                    }
        
                    $customFieldValue->setValue($value);
                }

                $this->customItemModel->save($customItem);
                $this->customItemModel->linkEntity($customItem, 'contact', (int) $contact->getId());
            }
        }
    }
}
