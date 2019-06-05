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
use InvalidArgumentException;
use Symfony\Component\HttpFoundation\Request;

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
        // This check can be removed once https://github.com/mautic-inc/mautic-cloud/pull/555 is merged to deployed.
        if (defined('\Mautic\ApiBundle\ApiEvents::API_ON_ENTITY_PRE_SAVE')) {
            return [
                 ApiEvents::API_ON_ENTITY_PRE_SAVE => 'validateCustomObjectsInContactRequest',
                 ApiEvents::API_ON_ENTITY_POST_SAVE => 'saveCustomObjectsInContactRequest',
            ];
        }

        return [];
    }

    /**
     * @param ApiEntityEvent $event
     */
    public function validateCustomObjectsInContactRequest(ApiEntityEvent $event): void
    {
        $this->saveCustomItems($event, true);
    }

    /**
     * @param ApiEntityEvent $event
     */
    public function saveCustomObjectsInContactRequest(ApiEntityEvent $event): void
    {
        $this->saveCustomItems($event);
    }

    private function saveCustomItems(ApiEntityEvent $event, $dryRun = false)
    {
        try {
            $customObjects = $this->getCustomObjectsFromContactCreateRequest($event->getRequest());
        } catch (InvalidArgumentException $e) {
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

                $this->customItemModel->save($customItem, $dryRun);

                if (!$dryRun) {
                    $this->customItemModel->linkEntity($customItem, 'contact', (int) $contact->getId());
                }
            }
        }
    }

    /**
     * @param Request $request
     * 
     * @return mixed[]
     * 
     * @throws InvalidArgumentException
     */
    private function getCustomObjectsFromContactCreateRequest(Request $request): array
    {
        if (!$this->configProvider->pluginIsEnabled() || !'/api/contacts/new' === $request->getPathInfo() || !$request->request->has('customObjects')) {
            throw new InvalidArgumentException("not a API request we care about");
        }

        $customObjects = $request->request->get('customObjects');

        if (!is_array($customObjects)) {
            throw new InvalidArgumentException("customObjects param in the request is not an array");
        }

        return $customObjects;
    }
}
