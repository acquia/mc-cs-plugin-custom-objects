<?php

declare(strict_types=1);

namespace MauticPlugin\CustomObjectsBundle\Controller\CustomItem\Api;

use MauticPlugin\CustomObjectsBundle\Controller\JsonController;
use MauticPlugin\CustomObjectsBundle\Exception\ForbiddenException;
use MauticPlugin\CustomObjectsBundle\Exception\NotFoundException;
use MauticPlugin\CustomObjectsBundle\Model\CustomItemModel;
use MauticPlugin\CustomObjectsBundle\Provider\CustomItemPermissionProvider;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class CustomController extends JsonController
{
    private CustomItemModel $customItemModel;
    private CustomItemPermissionProvider $permissionProvider;

    public function __construct(
        CustomItemModel $customItemModel,
        CustomItemPermissionProvider $permissionProvider
    ) {

        $this->customItemModel = $customItemModel;
        $this->permissionProvider = $permissionProvider;
    }

    public function unlinkAction(int $itemId, int $contactId): JsonResponse
    {
        try {
            $customItem = $this->customItemModel->fetchEntity($itemId);

            $this->permissionProvider->canEdit($customItem);

            if ($customItem->getCustomObject()->getRelationshipObject()) {
                try {
                    $childCustomItem = $customItem->findChildCustomItem();
                } catch (NotFoundException $e) {
                }

                if (isset($childCustomItem)) {
                    $this->customItemModel->delete($childCustomItem);
                }
            }

            $this->customItemModel->unlinkEntity($customItem, 'contact', $contactId);

            return new JsonResponse(['success' => true]);

        } catch (ForbiddenException|NotFoundException|\UnexpectedValueException $e) {
            return new JsonResponse([
                'message' => $e->getMessage(),
                'success' => false,
            ], $e->getCode());
        }
    }

    public function deleteAction(int $itemId): Response
    {
        try {
            $customItem = $this->customItemModel->fetchEntity($itemId);
            $this->permissionProvider->canDelete($customItem);
        } catch (NotFoundException|ForbiddenException $e) {
            return new JsonResponse([
                'message' => $e->getMessage(),
                'success' => false,
            ], Response::HTTP_NOT_FOUND);
        }

        $this->customItemModel->delete($customItem);

        return new JsonResponse(['success' => true]);
    }
}