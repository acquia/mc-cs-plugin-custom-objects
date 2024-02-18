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
     * @var SessionProviderFactory
     */
    private $sessionProviderFactory;

    /**
     * @var CustomItemRouteProvider
     */
    private $routeProvider;

    /**
     * @var CustomItemModel
     */
    private $customItemModel;

    public function __construct(
        SessionProviderFactory $sessionProviderFactory,
        CustomItemRouteProvider $routeProvider,
        CustomItemModel $customItemModel
    ) {
        $this->sessionProviderFactory = $sessionProviderFactory;
        $this->routeProvider          = $routeProvider;
        $this->customItemModel        = $customItemModel;
    }

    /**
     * @throws NotFoundException
     */
    public function cancelAction(int $objectId, ?int $itemId = null): Response
    {
        $page = $this->sessionProviderFactory->createItemProvider($objectId)->getPage();

        if ($itemId) {
            $customItem = $this->customItemModel->fetchEntity($itemId);
            $this->customItemModel->unlockEntity($customItem);
        }

        return $this->postActionRedirect(
            [
                'returnUrl'       => $this->routeProvider->buildListRoute($objectId, $page),
                'viewParameters'  => ['objectId' => $objectId, 'page' => $page],
                'contentTemplate' => 'MauticPlugin\CustomObjectsBundle\Controller\CustomItem\ListController::listAction',
                'passthroughVars' => [
                    'mauticContent' => 'customItem',
                ],
            ]
        );
    }
}
