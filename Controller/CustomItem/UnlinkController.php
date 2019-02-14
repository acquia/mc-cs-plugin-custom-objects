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

use MauticPlugin\CustomObjectsBundle\Model\CustomItemModel;
use MauticPlugin\CustomObjectsBundle\Provider\CustomItemPermissionProvider;
use MauticPlugin\CustomObjectsBundle\Exception\ForbiddenException;
use Symfony\Component\HttpFoundation\JsonResponse;
use MauticPlugin\CustomObjectsBundle\Controller\JsonController;
use Symfony\Component\Translation\TranslatorInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

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
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * @param CustomItemModel $customItemModel
     * @param CustomItemPermissionProvider $permissionProvider
     * @param TranslatorInterface $translator
     */
    public function __construct(
        CustomItemModel $customItemModel,
        CustomItemPermissionProvider $permissionProvider,
        TranslatorInterface $translator
    )
    {
        $this->customItemModel    = $customItemModel;
        $this->permissionProvider = $permissionProvider;
        $this->translator         = $translator;
    }

    /**
     * @param integer $objectId
     * @param string  $entityType
     * @param integer $entityId
     * 
     * @return JsonResponse
     */
    public function saveAction(int $itemId, string $entityType, int $entityId): JsonResponse
    {
        try {
            $this->permissionProvider->canViewAtAll();
            $this->unlinkBasedOnEntityType($itemId, $entityType, $entityId);
        } catch (ForbiddenException $e) {
            return new AccessDeniedException($e->getMessage(), $e);
        }

        $this->addFlash('notice', $this->translator->trans(
            'custom.item.unlinked',
            ['%itemId%' => $itemId, '%entityType%' => $entityType, '%entityId%' => $entityId],
            'flashes'
        ));

        return $this->renderJson();
    }

    /**
     * @param integer $itemId
     * @param string  $entityType
     * @param integer $entityId
     * 
     * @throws \UnexpectedValueException
     */
    private function unlinkBasedOnEntityType(int $itemId, string $entityType, int $entityId): void
    {
        switch ($entityType) {
            case 'contact':
                $this->customItemModel->unlinkContact($itemId, $entityId);
                break;
            default:
                throw new \UnexpectedValueException("Entity {$entityType} cannot be linked to a custom item");
                break;
        }
    }
}