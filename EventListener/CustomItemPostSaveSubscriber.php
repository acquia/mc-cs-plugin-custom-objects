<?php

declare(strict_types=1);

namespace MauticPlugin\CustomObjectsBundle\EventListener;

use MauticPlugin\CustomObjectsBundle\CustomItemEvents;
use MauticPlugin\CustomObjectsBundle\Entity\CustomObject;
use MauticPlugin\CustomObjectsBundle\Event\CustomItemEvent;
use MauticPlugin\CustomObjectsBundle\Model\CustomItemModel;
use MauticPlugin\CustomObjectsBundle\Provider\CustomItemRouteProvider;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RequestStack;

class CustomItemPostSaveSubscriber implements EventSubscriberInterface
{
    /**
     * @var CustomItemModel
     */
    private $customItemModel;

    /**
     * @var RequestStack
     */
    private $requestStack;

    public function __construct(CustomItemModel $customItemModel, RequestStack $requestStack)
    {
        $this->customItemModel = $customItemModel;
        $this->requestStack    = $requestStack;
    }

    public static function getSubscribedEvents()
    {
        return [
            CustomItemEvents::ON_CUSTOM_ITEM_POST_SAVE => 'postSave',
        ];
    }

    /**
     * Links the master object item with the entityType after a relationship object is created.
     */
    public function postSave(CustomItemEvent $event): void
    {
        $relationshipObjectItem = $event->getCustomItem();
        $request                = $this->requestStack->getCurrentRequest();

        if (
            CustomObject::TYPE_MASTER === $relationshipObjectItem->getCustomObject()->getType()
            || CustomItemRouteProvider::ROUTE_LINK_FORM_SAVE !== $request->attributes->get('_route')
        ) {
            return;
        }

        $masterObjectItem = $this->customItemModel->fetchEntity((int) $request->attributes->get('itemId'));
        $entityType       = $request->attributes->get('entityType');
        $entityId         = (int) $request->attributes->get('entityId');

        // Links the master object item to the entityType defined in the request. eg: contact
        $this->customItemModel->linkEntity($masterObjectItem, $entityType, $entityId);

        // Links the relationship object item to the master object item
        $this->customItemModel->linkEntity($relationshipObjectItem, 'customItem', $masterObjectItem->getId());

        // Links the relationship object item to the entityType defined in the request. eg: contact
        $this->customItemModel->linkEntity($relationshipObjectItem, $entityType, $entityId);
    }
}
