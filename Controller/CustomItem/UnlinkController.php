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
    /**
     * @var CustomItemModel
     */
    private $customItemModel;

    /**
     * @var CustomItemPermissionProvider
     */
    private $permissionProvider;

    /**
     * @var FlashBag
     */
    private $flashBag;

    public function __construct(
        CustomItemModel $customItemModel,
        CustomItemPermissionProvider $permissionProvider,
        FlashBag $flashBag
    ) {
        $this->customItemModel    = $customItemModel;
        $this->permissionProvider = $permissionProvider;
        $this->flashBag           = $flashBag;
    }

    public function saveAction(int $itemId, string $entityType, int $entityId): JsonResponse
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

            $this->customItemModel->unlinkEntity($customItem, $entityType, $entityId);

            $this->flashBag->add(
                'custom.item.unlinked',
                ['%itemId%' => $customItem->getId(), '%itemName%' => $customItem->getName(), '%entityType%' => $entityType, '%entityId%' => $entityId]
            );
        } catch (ForbiddenException | NotFoundException | UnexpectedValueException $e) {
            $this->flashBag->add($e->getMessage(), [], FlashBag::LEVEL_ERROR);
        }

        return $this->renderJson();
    }
}
