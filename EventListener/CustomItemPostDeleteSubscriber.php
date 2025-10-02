<?php

declare(strict_types=1);

namespace MauticPlugin\CustomObjectsBundle\EventListener;

use MauticPlugin\CustomObjectsBundle\CustomItemEvents;
use MauticPlugin\CustomObjectsBundle\Event\CustomItemEvent;
use MauticPlugin\CustomObjectsBundle\Repository\CustomItemXrefContactRepository;
use MauticPlugin\CustomObjectsBundle\Repository\CustomItemXrefCustomItemRepository;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

final class CustomItemPostDeleteSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private CustomItemXrefCustomItemRepository $customItemXrefCustomItemRepository,
        private CustomItemXrefContactRepository $customItemXrefContactRepository
    ) {
    }

    public static function getSubscribedEvents()
    {
        return [
            CustomItemEvents::ON_CUSTOM_ITEM_POST_DELETE => 'onPostDelete',
        ];
    }

    /**
     * Links the master object item with the entityType after a relationship object is created.
     */
    public function onPostDelete(CustomItemEvent $event): void
    {
        $this->customItemXrefCustomItemRepository->deleteAllLinksForCustomItem($event->getCustomItem()->deletedId);
        $this->customItemXrefContactRepository->deleteAllLinksForCustomItem($event->getCustomItem()->deletedId);
    }
}
