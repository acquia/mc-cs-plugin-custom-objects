<?php

declare(strict_types=1);

namespace MauticPlugin\CustomObjectsBundle\Controller\CustomItem;

use Mautic\CoreBundle\Service\FlashBag;
use MauticPlugin\CustomObjectsBundle\Controller\JsonController;
use MauticPlugin\CustomObjectsBundle\Exception\ForbiddenException;
use MauticPlugin\CustomObjectsBundle\Exception\NotFoundException;
use MauticPlugin\CustomObjectsBundle\Model\CustomItemModel;
use MauticPlugin\CustomObjectsBundle\Provider\CustomItemPermissionProvider;
use Symfony\Component\HttpFoundation\JsonResponse;
use UnexpectedValueException;

class UnlinkController extends JsonController
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

            if ($customItem->getCustomObject()->getRelationshipObject()) {
                try {
                    $childCustomItem = $customItem->findChildCustomItem();
                } catch (NotFoundException $e) {
                }

                if (isset($childCustomItem)) {
                    $customItemModel->delete($childCustomItem);
                }
            }

            $customItemModel->unlinkEntity($customItem, $entityType, $entityId);

            $flashBag->add(
                'custom.item.unlinked',
                ['%itemId%' => $customItem->getId(), '%itemName%' => $customItem->getName(), '%entityType%' => $entityType, '%entityId%' => $entityId]
            );
        } catch (ForbiddenException|NotFoundException|UnexpectedValueException $e) {
            $flashBag->add($e->getMessage(), [], FlashBag::LEVEL_ERROR);
        }

        return $this->renderJson();
    }
}
