<?php

declare(strict_types=1);

namespace MauticPlugin\CustomObjectsBundle\Controller\CustomObject;

use Mautic\CoreBundle\Controller\CommonController;
use MauticPlugin\CustomObjectsBundle\Model\CustomObjectModel;
use MauticPlugin\CustomObjectsBundle\Provider\CustomObjectRouteProvider;
use MauticPlugin\CustomObjectsBundle\Provider\SessionProviderFactory;
use Symfony\Component\HttpFoundation\Response;

class CancelController extends CommonController
{
    public function cancelAction(
        SessionProviderFactory $sessionProviderFactory,
        CustomObjectRouteProvider $routeProvider,
        CustomObjectModel $customObjectModel,
        ?int $objectId
    ): Response {
        $page = $sessionProviderFactory->createObjectProvider()->getPage();

        if ($objectId) {
            $customObject = $customObjectModel->fetchEntity($objectId);
            $customObjectModel->unlockEntity($customObject);
        }

        return $this->postActionRedirect(
            [
                'returnUrl'       => $routeProvider->buildListRoute($page),
                'viewParameters'  => ['page' => $page],
                'contentTemplate' => 'MauticPlugin\CustomObjectsBundle\Controller\CustomObject\ListController:listAction',
                'passthroughVars' => [
                    'mauticContent' => 'customObject',
                ],
            ]
        );
    }
}
