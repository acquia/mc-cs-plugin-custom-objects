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
    /**
     * @var CustomItemXrefCustomItemRepository
     */
    private $customItemXrefCustomItemRepository;

    /**
     * @var CustomItemXrefContactRepository
     */
    private $customItemXrefContactRepository;

    public function __construct(
        CustomItemXrefCustomItemRepository $customItemXrefCustomItemRepository,
        CustomItemXrefContactRepository $customItemXrefContactRepository
    ) {
        $this->customItemXrefCustomItemRepository = $customItemXrefCustomItemRepository;
        $this->customItemXrefContactRepository    = $customItemXrefContactRepository;
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
