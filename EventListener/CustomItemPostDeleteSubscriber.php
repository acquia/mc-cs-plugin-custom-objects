<?php

declare(strict_types=1);

/*
* @copyright   2020 Mautic, Inc. All rights reserved
* @author      Mautic, Inc.
*
* @link        https://mautic.com
*
* @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
*/

namespace MauticPlugin\CustomObjectsBundle\EventListener;

use MauticPlugin\CustomObjectsBundle\CustomItemEvents;
use MauticPlugin\CustomObjectsBundle\Event\CustomItemEvent;
use MauticPlugin\CustomObjectsBundle\Repository\CustomItemXrefCustomItemRepository;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use MauticPlugin\CustomObjectsBundle\Repository\CustomItemXrefContactRepository;

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
