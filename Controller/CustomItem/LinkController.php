<?php

declare(strict_types=1);

/*
 * @copyright   2018 Mautic, Inc. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.com
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\CustomObjectsBundle\Controller\CustomItem;

use MauticPlugin\CustomObjectsBundle\Model\CustomItemModel;
use MauticPlugin\CustomObjectsBundle\Provider\CustomItemPermissionProvider;
use MauticPlugin\CustomObjectsBundle\Exception\ForbiddenException;
use Symfony\Component\HttpFoundation\JsonResponse;
use MauticPlugin\CustomObjectsBundle\Controller\JsonController;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use MauticPlugin\CustomObjectsBundle\Exception\NotFoundException;
use UnexpectedValueException;
use Mautic\CoreBundle\Service\FlashBag;
use MauticPlugin\CustomObjectsBundle\Event\CustomItemXrefEntityDiscoveryEvent;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use MauticPlugin\CustomObjectsBundle\CustomItemEvents;
use MauticPlugin\CustomObjectsBundle\Event\CustomItemXrefEntityEvent;

class LinkController extends JsonController
{
    /**
     * @var CustomItemModel
     */
    private $customItemModel;

    /**
     * @var EventDispatcherInterface
     */
    private $dispatcher;

    /**
     * @var CustomItemPermissionProvider
     */
    private $permissionProvider;

    /**
     * @var FlashBag
     */
    private $flashBag;

    /**
     * @param CustomItemModel              $customItemModel
     * @param EventDispatcherInterface     $dispatcher
     * @param CustomItemPermissionProvider $permissionProvider
     * @param FlashBag                     $flashBag
     */
    public function __construct(
        CustomItemModel $customItemModel,
        EventDispatcherInterface $dispatcher,
        CustomItemPermissionProvider $permissionProvider,
        FlashBag $flashBag
    ) {
        $this->customItemModel    = $customItemModel;
        $this->dispatcher         = $dispatcher;
        $this->permissionProvider = $permissionProvider;
        $this->flashBag           = $flashBag;
    }

    /**
     * @param int    $itemId
     * @param string $entityType
     * @param int    $entityId
     *
     * @return JsonResponse
     */
    public function saveAction(int $itemId, string $entityType, int $entityId): JsonResponse
    {
        try {
            $customItem = $this->customItemModel->fetchEntity($itemId);
            
            $this->permissionProvider->canEdit($customItem);

            $event = $this->dispatcher->dispatch(
                CustomItemEvents::ON_CUSTOM_ITEM_LINK_ENTITY_DISCOVERY,
                new CustomItemXrefEntityDiscoveryEvent($customItem, $entityType, $entityId)
            );

            if (null === $event->getXrefEntity()) {
                throw new UnexpectedValueException("Entity {$entityType} was not able to be linked to {$customItem->getName()} ({$customItem->getId()})");
            }

            $this->dispatcher->dispatch(
                CustomItemEvents::ON_CUSTOM_ITEM_LINK_ENTITY,
                new CustomItemXrefEntityEvent($event->getXrefEntity())
            );

            $this->flashBag->add(
                'custom.item.linked',
                ['%itemId%' => $customItem->getId(), '%itemName%' => $customItem->getName(), '%entityType%' => $entityType, '%entityId%' => $entityId]
            );
        } catch (UniqueConstraintViolationException $e) {
            $this->flashBag->add(
                'custom.item.error.link.exists.already',
                ['%itemId%' => $itemId, '%entityType%' => $entityType, '%entityId%' => $entityId],
                FlashBag::LEVEL_ERROR
            );
        } catch (ForbiddenException | NotFoundException | UnexpectedValueException $e) {
            $this->flashBag->add($e->getMessage(), [], FlashBag::LEVEL_ERROR);
        }

        return $this->renderJson();
    }
}
