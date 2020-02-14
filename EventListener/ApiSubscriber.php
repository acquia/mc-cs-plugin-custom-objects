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

use InvalidArgumentException;
use Mautic\ApiBundle\ApiEvents;
use Mautic\ApiBundle\Event\ApiEntityEvent;
use Mautic\LeadBundle\Entity\Lead;
use MauticPlugin\CustomObjectsBundle\Entity\CustomItem;
use MauticPlugin\CustomObjectsBundle\Entity\CustomObject;
use MauticPlugin\CustomObjectsBundle\Exception\InvalidValueException;
use MauticPlugin\CustomObjectsBundle\Exception\NotFoundException;
use MauticPlugin\CustomObjectsBundle\Model\CustomItemModel;
use MauticPlugin\CustomObjectsBundle\Model\CustomObjectModel;
use MauticPlugin\CustomObjectsBundle\Provider\ConfigProvider;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class ApiSubscriber implements EventSubscriberInterface
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
            ApiEvents::API_ON_ENTITY_PRE_SAVE  => 'validateCustomObjectsInContactRequest',
            ApiEvents::API_ON_ENTITY_POST_SAVE => 'saveCustomObjectsInContactRequest',
        ];
    }

    public function validateCustomObjectsInContactRequest(ApiEntityEvent $event): void
    {
        $this->saveCustomItems($event, true);
    }

    public function saveCustomObjectsInContactRequest(ApiEntityEvent $event): void
    {
        $this->saveCustomItems($event);
    }

    /**
     * @throws NotFoundException
     * @throws InvalidValueException
     */
    private function saveCustomItems(ApiEntityEvent $event, bool $dryRun = false): void
    {
        try {
            $customObjectsPayload = $this->getCustomObjectsFromContactCreateRequest(
                $event->getEntityRequestParameters(),
                $event->getRequest()
            );
        } catch (InvalidArgumentException $e) {
            return;
        }

        /** @var Lead $contact */
        $contact = $event->getEntity();

        foreach ($customObjectsPayload['data'] as $customObjectData) {
            if (empty($customObjectData['data']) || !is_array($customObjectData['data'])) {
                continue;
            }

            $customObject = $this->getCustomObject($customObjectData);

            foreach ($customObjectData['data'] as $customItemData) {
                $customItem = $this->getCustomItem($customObject, $customItemData);
                $customItem = $this->populateCustomItemWithRequestData($customItem, $customItemData);
                $customItem->setDefaultValuesForMissingFields();

                $this->customItemModel->save($customItem, $dryRun);

                if (!$dryRun) {
                    $this->customItemModel->linkEntity($customItem, 'contact', (int) $contact->getId());
                }
            }
        }
    }

    /**
     * @param mixed[] $entityRequestParameters
     *
     * @return mixed[]
     *
     * @throws InvalidArgumentException
     */
    private function getCustomObjectsFromContactCreateRequest(array $entityRequestParameters, Request $request): array
    {
        if (!$this->configProvider->pluginIsEnabled()) {
            throw new InvalidArgumentException('Custom Object Plugin is disabled');
        }

        if (1 !== preg_match('/^\/api\/contacts\/.*(new|edit)/', $request->getPathInfo())) {
            throw new InvalidArgumentException('Not a API request we care about');
        }

        if (empty($entityRequestParameters['customObjects']['data']) || !is_array($entityRequestParameters['customObjects']['data'])) {
            throw new InvalidArgumentException('The request payload does not contain any custom items in the customObjects attribute.');
        }

        return $entityRequestParameters['customObjects'];
    }

    /**
     * @param mixed[] $customObjectData
     *
     * @throws NotFoundException|InvalidArgumentException
     */
    private function getCustomObject(array $customObjectData): CustomObject
    {
        try {
            if (isset($customObjectData['id'])) {
                return $this->customObjectModel->fetchEntity((int) $customObjectData['id']);
            } elseif (isset($customObjectData['alias'])) {
                return $this->customObjectModel->fetchEntityByAlias($customObjectData['alias']);
            }

            throw new InvalidArgumentException('customObject[data][][id] or customObject[data][][alias] must exist in the request to identify a Custom Object.', Response::HTTP_BAD_REQUEST);
        } catch (NotFoundException $e) {
            throw new NotFoundException($e->getMessage(), Response::HTTP_BAD_REQUEST, $e);
        }
    }

    /**
     * @param mixed[] $customItemData
     *
     * @throws NotFoundException
     */
    private function getCustomItem(CustomObject $customObject, array $customItemData): CustomItem
    {
        if (empty($customItemData['id'])) {
            return new CustomItem($customObject);
        }

        try {
            $customItem = $this->customItemModel->fetchEntity((int) $customItemData['id']);

            return $this->customItemModel->populateCustomFields($customItem);
        } catch (NotFoundException $e) {
            throw new NotFoundException($e->getMessage(), Response::HTTP_BAD_REQUEST, $e);
        }
    }

    /**
     * @param mixed[] $customItemData
     *
     * @throws NotFoundException
     */
    private function populateCustomItemWithRequestData(CustomItem $customItem, array $customItemData): CustomItem
    {
        if (!empty($customItemData['name'])) {
            $customItem->setName($customItemData['name']);
        }

        if (!empty($customItemData['attributes']) && is_array($customItemData['attributes'])) {
            foreach ($customItemData['attributes'] as $fieldAlias => $value) {
                try {
                    $customFieldValue = $customItem->findCustomFieldValueForFieldAlias($fieldAlias);
                    $customFieldValue->setValue($value);
                } catch (NotFoundException $e) {
                    try {
                        $customItem->createNewCustomFieldValueByFieldAlias($fieldAlias, $value);
                    } catch (NotFoundException $e) {
                        throw new NotFoundException($e->getMessage(), Response::HTTP_BAD_REQUEST, $e);
                    }
                }
            }
        }

        return $customItem;
    }
}
