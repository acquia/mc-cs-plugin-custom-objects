<?php

declare(strict_types=1);

namespace MauticPlugin\CustomObjectsBundle\Controller\CustomItem;

use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Mautic\CoreBundle\Service\FlashBag;
use MauticPlugin\CustomObjectsBundle\Controller\JsonController;
use MauticPlugin\CustomObjectsBundle\Exception\ForbiddenException;
use MauticPlugin\CustomObjectsBundle\Exception\NotFoundException;
use MauticPlugin\CustomObjectsBundle\Model\CustomItemModel;
use MauticPlugin\CustomObjectsBundle\Provider\CustomItemPermissionProvider;
use Symfony\Component\HttpFoundation\JsonResponse;
use UnexpectedValueException;

class LinkController extends JsonController
{
    public function saveAction(
        CustomItemModel $customItemModel,
        CustomItemPermissionProvider $permissionProvider,
        FlashBag $flashBag,
        int $itemId,
        string $entityType,
        int $entityId
    ): JsonResponse {
        try {
            $customItem = $customItemModel->fetchEntity($itemId);

            $permissionProvider->canEdit($customItem);

            $customItemModel->linkEntity($customItem, $entityType, $entityId);

            $flashBag->add(
                'custom.item.linked',
                ['%itemId%' => $customItem->getId(), '%itemName%' => $customItem->getName(), '%entityType%' => $entityType, '%entityId%' => $entityId]
            );
        } catch (UniqueConstraintViolationException $e) {
            $flashBag->add(
                'custom.item.error.link.exists.already',
                ['%itemId%' => $itemId, '%entityType%' => $entityType, '%entityId%' => $entityId],
                FlashBag::LEVEL_ERROR
            );
        } catch (ForbiddenException|NotFoundException|UnexpectedValueException $e) {
            $flashBag->add($e->getMessage(), [], FlashBag::LEVEL_ERROR);
        }

        return $this->renderJson();
    }
}
