<?php

declare(strict_types=1);

namespace MauticPlugin\CustomObjectsBundle\Controller\CustomItem;

use Mautic\ApiBundle\Controller\CommonApiController;
use MauticPlugin\CustomObjectsBundle\Exception\ForbiddenException;
use MauticPlugin\CustomObjectsBundle\Exception\NotFoundException;
use MauticPlugin\CustomObjectsBundle\Model\CustomItemModel;
use MauticPlugin\CustomObjectsBundle\Provider\CustomItemPermissionProvider;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Response;

class ApiController extends CommonApiController
{
    private CustomItemModel $customItemModel;
    private CustomItemPermissionProvider $permissionProvider;
    private LoggerInterface $logger;

    public function __construct(
        CustomItemModel $customItemModel,
        CustomItemPermissionProvider $permissionProvider,
        LoggerInterface $logger
    ) {
        $this->customItemModel        = $customItemModel;
        $this->permissionProvider     = $permissionProvider;
        $this->logger                 = $logger;
    }

    public function deleteAction(int $itemId): Response
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

        $this->logger->info(sprintf('Item %d - %s has been deleted',
            $itemId,
            $customItem->getName(),
        ));

        $view = $this->view(['success' => true], Response::HTTP_OK);

        return $this->handleView($view);
    }
}
