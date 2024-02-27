<?php

declare(strict_types=1);

namespace MauticPlugin\CustomObjectsBundle\Controller\CustomItem;

use Mautic\CoreBundle\Controller\CommonController;
use Mautic\CoreBundle\Service\FlashBag;
use MauticPlugin\CustomObjectsBundle\Exception\ForbiddenException;
use MauticPlugin\CustomObjectsBundle\Exception\NotFoundException;
use MauticPlugin\CustomObjectsBundle\Model\CustomItemModel;
use MauticPlugin\CustomObjectsBundle\Provider\CustomItemPermissionProvider;
use MauticPlugin\CustomObjectsBundle\Provider\CustomItemRouteProvider;
use MauticPlugin\CustomObjectsBundle\Provider\SessionProviderFactory;
use Symfony\Component\HttpFoundation\Response;

class DeleteController extends CommonController
{
    public function deleteAction(
        CustomItemModel $customItemModel,
        SessionProviderFactory $sessionProviderFactory,
        FlashBag $flashBag,
        CustomItemPermissionProvider $permissionProvider,
        CustomItemRouteProvider $routeProvider,
        int $objectId,
        int $itemId
    ): Response {
        try {
            $customItem = $customItemModel->fetchEntity($itemId);
            $permissionProvider->canDelete($customItem);
        } catch (NotFoundException $e) {
            return $this->notFound($e->getMessage());
        } catch (ForbiddenException $e) {
            return $this->accessDenied(false, $e->getMessage());
        }

        $customItemModel->delete($customItem);

        $flashBag->add(
            'mautic.core.notice.deleted',
            [
                '%name%' => $customItem->getName(),
                '%id%'   => $customItem->getId(),
            ]
        );

        $page = $sessionProviderFactory->createItemProvider($objectId)->getPage();

        return $this->postActionRedirect(
            [
                'returnUrl'       => $routeProvider->buildListRoute($objectId, $page),
                'viewParameters'  => ['objectId' => $objectId, 'page' => $page],
                'contentTemplate' => 'MauticPlugin\CustomObjectsBundle\Controller\CustomItem\ListController::listAction',
                'passthroughVars' => [
                    'mauticContent' => 'customItem',
                ],
            ]
        );
    }
}
