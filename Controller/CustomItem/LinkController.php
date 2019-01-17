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
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use MauticPlugin\CustomObjectsBundle\Provider\CustomItemPermissionProvider;
use MauticPlugin\CustomObjectsBundle\Exception\ForbiddenException;
use Mautic\CoreBundle\Helper\InputHelper;
use MauticPlugin\CustomObjectsBundle\DTO\TableConfig;
use MauticPlugin\CustomObjectsBundle\Entity\CustomItem;
use Symfony\Component\HttpFoundation\JsonResponse;
use MauticPlugin\CustomObjectsBundle\DTO\TableFilterConfig;

class LinkController extends Controller
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
     * @param CustomItemModel $customItemModel
     * @param CustomItemPermissionProvider $permissionProvider
     */
    public function __construct(
        CustomItemModel $customItemModel,
        CustomItemPermissionProvider $permissionProvider
    )
    {
        $this->customItemModel    = $customItemModel;
        $this->permissionProvider = $permissionProvider;
    }

    /**
     * @param integer $objectId
     * @param string  $entityType
     * @param integer $entityId
     * 
     * @return JsonResponse
     */
    public function saveAction(int $itemId, string $entityType, int $entityId)
    {
        try {
            $this->permissionProvider->canViewAtAll();
        } catch (ForbiddenException $e) {
            return new AccessDeniedException($e->getMessage(), $e);
        }

        switch ($entityType) {
            case 'contact':
                $this->customItemModel->linkContact($itemId, $entityId);
                break;
            default:
                throw new \UnexpectedValueException("Entity {$entityType} cannot be linked to a custom item");
                break;
        }


        return new JsonResponse();
    }
}