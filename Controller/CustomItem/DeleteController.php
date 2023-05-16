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
    /**
     * @var CustomItemModel
     */
    private $customItemModel;

    /**
     * @var SessionProviderFactory
     */
    private $sessionProviderFactory;

    /**
     * @var CustomItemPermissionProvider
     */
    private $permissionProvider;

    /**
     * @var CustomItemRouteProvider
     */
    private $routeProvider;

    /**
     * @var FlashBag
     */
    private $flashBag;

    public function __construct(
        CustomItemModel $customItemModel,
        SessionProviderFactory $sessionProviderFactory,
        FlashBag $flashBag,
        CustomItemPermissionProvider $permissionProvider,
        CustomItemRouteProvider $routeProvider
    ) {
        $this->customItemModel        = $customItemModel;
        $this->sessionProviderFactory = $sessionProviderFactory;
        $this->flashBag               = $flashBag;
        $this->permissionProvider     = $permissionProvider;
        $this->routeProvider          = $routeProvider;
    }

    public function deleteAction(int $objectId, int $itemId): Response
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

        $this->flashBag->add(
            'mautic.core.notice.deleted',
            [
                '%name%' => $customItem->getName(),
                '%id%'   => $customItem->getId(),
            ]
        );

        $page = $this->sessionProviderFactory->createItemProvider($objectId)
            ->getPage();

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
