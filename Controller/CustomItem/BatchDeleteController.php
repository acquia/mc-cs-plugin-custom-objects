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
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class BatchDeleteController extends CommonController
{
    public function deleteAction(
        Request $request,
        CustomItemModel $customItemModel,
        SessionProviderFactory $sessionProviderFactory,
        CustomItemPermissionProvider $permissionProvider,
        CustomItemRouteProvider $routeProvider,
        FlashBag $flashBag,
        int $objectId
    ): Response {
        $itemIds  = json_decode($request->get('ids', '[]'), true);
        $page     = $sessionProviderFactory->createItemProvider($objectId)->getPage();
        $notFound = [];
        $denied   = [];
        $deleted  = [];

        foreach ($itemIds as $itemId) {
            try {
                $customItem = $customItemModel->fetchEntity((int) $itemId);
                $permissionProvider->canDelete($customItem);
                $customItemModel->delete($customItem);
                $deleted[] = $itemId;
            } catch (NotFoundException $e) {
                $notFound[] = $itemId;
            } catch (ForbiddenException $e) {
                $denied[] = $itemId;
            }
        }

        if ($deleted) {
            $flashBag->add(
                'mautic.core.notice.batch_deleted',
                ['%count%' => count($deleted)]
            );
        }

        if ($notFound) {
            $flashBag->add(
                'custom.item.error.items.not.found',
                ['%ids%' => implode(',', $notFound)],
                FlashBag::LEVEL_ERROR
            );
        }

        if ($denied) {
            $flashBag->add(
                'custom.item.error.items.denied',
                ['%ids%' => implode(',', $denied)],
                FlashBag::LEVEL_ERROR
            );
        }

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
