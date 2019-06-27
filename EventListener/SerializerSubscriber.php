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

use JMS\Serializer\EventDispatcher\EventSubscriberInterface;
use JMS\Serializer\EventDispatcher\Events;
use JMS\Serializer\EventDispatcher\ObjectEvent;
use MauticPlugin\CustomObjectsBundle\Provider\ConfigProvider;
use MauticPlugin\CustomObjectsBundle\Model\CustomItemModel;
use Mautic\LeadBundle\Entity\Lead;
use MauticPlugin\CustomObjectsBundle\DTO\TableConfig;
use MauticPlugin\CustomObjectsBundle\Repository\CustomItemXrefContactRepository;
use MauticPlugin\CustomObjectsBundle\Entity\CustomItem;
use MauticPlugin\CustomObjectsBundle\Entity\CustomFieldValueInterface;
use Doctrine\Common\Collections\Collection;
use Symfony\Component\HttpFoundation\RequestStack;
use MauticPlugin\CustomObjectsBundle\Entity\CustomObject;
use MauticPlugin\CustomObjectsBundle\Exception\UndefinedTransformerException;

class SerializerSubscriber implements EventSubscriberInterface
{
    /**
     * @var ConfigProvider
     */
    private $configProvider;

    /**
     * @var CustomItemXrefContactRepository
     */
    private $customItemXrefContactRepository;

    /**
     * @var CustomItemModel
     */
    private $customItemModel;

    /**
     * @var RequestStack
     */
    private $requestStack;

    /**
     * @param ConfigProvider                  $configProvider
     * @param CustomItemXrefContactRepository $customItemXrefContactRepository
     * @param CustomItemModel                 $customItemModel
     * @param RequestStack                    $requestStack
     */
    public function __construct(
        ConfigProvider $configProvider,
        CustomItemXrefContactRepository $customItemXrefContactRepository,
        CustomItemModel $customItemModel,
        RequestStack $requestStack
    ) {
        $this->configProvider                  = $configProvider;
        $this->customItemXrefContactRepository = $customItemXrefContactRepository;
        $this->customItemModel                 = $customItemModel;
        $this->requestStack                    = $requestStack;
    }

    /**
     * @return mixed[]
     */
    public static function getSubscribedEvents(): array
    {
        return [
            [
                'event'  => Events::POST_SERIALIZE,
                'method' => 'addCustomItemsIntoContactResponse',
            ],
        ];
    }

    /**
     * @param ObjectEvent $event
     */
    public function addCustomItemsIntoContactResponse(ObjectEvent $event): void
    {
        $limit    = 10;
        $page     = 1;
        $orderDir = 'DESC';
        $request  = $this->requestStack->getCurrentRequest();

        if (null === $request) {
            return;
        }

        if (!$request->get('includeCustomObjects', false)) {
            return;
        }

        /** @var Lead $contact */
        $contact = $event->getObject();

        if (!$contact instanceof Lead) {
            return;
        }

        if (!$this->configProvider->pluginIsEnabled()) {
            return;
        }

        $tableConfig   = new TableConfig($limit, $page, CustomObject::TABLE_ALIAS.'.dateAdded', $orderDir);
        $customObjects = $this->customItemXrefContactRepository->getCustomObjectsRelatedToContact($contact, $tableConfig);

        if (empty($customObjects)) {
            return;
        }

        $payload = [
            'data' => [],
            'meta' => [
                'sort' => '-dateAdded',
                'page' => [
                    'number' => $page,
                    'size'   => $limit,
                ],
            ],
        ];

        foreach ($customObjects as $customObject) {
            $tableConfig = new TableConfig($limit, $page, CustomItem::TABLE_ALIAS.'.dateAdded', $orderDir);
            $tableConfig->addParameter('customObjectId', $customObject['id']);
            $tableConfig->addParameter('filterEntityId', $contact->getId());
            $tableConfig->addParameter('filterEntityType', 'contact');
            $customItems  = $this->customItemModel->getTableData($tableConfig);
            $itemsPayload = [];

            if (count($customItems)) {
                /** @var CustomItem $customItem */
                foreach ($customItems as $customItem) {
                    $this->customItemModel->populateCustomFields($customItem);
                    $itemsPayload[] = $this->serializeCustomItem($customItem);
                }

                $payload['data'][] = [
                    'id'    => $customObject['id'],
                    'alias' => $customObject['alias'],
                    'data'  => $itemsPayload,
                    'meta'  => [
                        'sort' => '-dateAdded',
                        'page' => [
                            'number' => $page,
                            'size'   => $limit,
                        ],
                    ],
                ];
            }
        }

        $event->getContext()->getVisitor()->addData('customObjects', $payload);
    }

    /**
     * @param CustomItem $customItem
     *
     * @return mixed[]
     */
    private function serializeCustomItem(CustomItem $customItem): array
    {
        return [
            'id'           => $customItem->getId(),
            'name'         => $customItem->getName(),
            'language'     => $customItem->getLanguage(),
            'category'     => $customItem->getCategory(),
            'isPublished'  => $customItem->getIsPublished(),
            'dateAdded'    => $customItem->getDateAdded()->format(DATE_ATOM),
            'dateModified' => $customItem->getDateModified()->format(DATE_ATOM),
            'createdBy'    => $customItem->getCreatedBy(),
            'modifiedBy'   => $customItem->getModifiedBy(),
            'attributes'   => $this->serializeCustomFieldValues($customItem->getCustomFieldValues()),
        ];
    }

    /**
     * @param Collection|CustomFieldValueInterface[] $customFieldValues
     *
     * @return mixed[]
     */
    private function serializeCustomFieldValues(Collection $customFieldValues): array
    {
        $serializedValues = [];

        /** @var CustomFieldValueInterface $customFieldValue */
        foreach ($customFieldValues as $customFieldValue) {
            try {
                $transformer = $customFieldValue->getCustomField()->getTypeObject()->createApiValueTransformer();
                $value       = $transformer->reverseTransform($customFieldValue->getValue());
            } catch (UndefinedTransformerException $e) {
                $value = $customFieldValue->getValue();
            }

            $serializedValues[$customFieldValue->getCustomField()->getAlias()] = $value;
        }

        return $serializedValues;
    }
}
