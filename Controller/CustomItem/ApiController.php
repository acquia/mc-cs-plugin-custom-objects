<?php

declare(strict_types=1);

namespace MauticPlugin\CustomObjectsBundle\Controller\CustomItem;

use Mautic\ApiBundle\Controller\CommonApiController;
use MauticPlugin\CustomObjectsBundle\Exception\ForbiddenException;
use MauticPlugin\CustomObjectsBundle\Exception\NotFoundException;
use MauticPlugin\CustomObjectsBundle\Model\CustomItemModel;
use MauticPlugin\CustomObjectsBundle\Provider\CustomItemPermissionProvider;

class ApiController extends CommonApiController
{
    /**
     * @var CustomItemModel
     */
    private $customItemModel;

    /**
     * @var CustomItemPermissionProvider
     */
    private $permissionProvider; 
    
    public function __construct(
        CustomItemModel $customItemModel,
        CustomItemPermissionProvider $permissionProvider
    )
    {
        $this->customItemModel = $customItemModel;
        $this->permissionProvider = $permissionProvider;
    }

    public function deleteAction(int $itemId)
    {
        try {
            $customItem = $this->customItemModel->fetchEntity($itemId);
            $this->permissionProvider->canDelete($customItem);
        } catch (NotFoundException $e) {
            return $this->notFound($e->getMessage());
        } catch (ForbiddenException $e) {
            return $this->accessDenied(false, $e->getMessage());
        }

        $this->customItemModel->delete($customItem);

        $res = $this->view('Deleted successfully', 200);

        return $this->handleView($res);
    }
}