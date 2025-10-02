<?php

declare(strict_types=1);

namespace MauticPlugin\CustomObjectsBundle\Controller\CustomItem;

use Mautic\CoreBundle\Controller\CommonController;
use MauticPlugin\CustomObjectsBundle\Exception\NotFoundException;
use MauticPlugin\CustomObjectsBundle\Model\CustomItemModel;
use MauticPlugin\CustomObjectsBundle\Provider\CustomItemRouteProvider;
use MauticPlugin\CustomObjectsBundle\Provider\SessionProviderFactory;
use Symfony\Component\HttpFoundation\Response;

class CancelController extends CommonController
{
    /**
     * @throws NotFoundException
     */
    public function cancelAction(
        SessionProviderFactory $sessionProviderFactory,
        CustomItemRouteProvider $routeProvider,
        CustomItemModel $customItemModel,
        int $objectId,
        ?int $itemId = null
    ): Response {
        $page = $sessionProviderFactory->createItemProvider($objectId)->getPage();

        if ($itemId) {
            $customItem = $customItemModel->fetchEntity($itemId);
            $customItemModel->unlockEntity($customItem);
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
