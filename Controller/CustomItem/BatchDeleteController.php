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
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;

class BatchDeleteController extends CommonController
{
    /**
     * @var RequestStack
     */
    private $requestStack;

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
        RequestStack $requestStack,
        CustomItemModel $customItemModel,
        SessionProviderFactory $sessionProviderFactory,
        CustomItemPermissionProvider $permissionProvider,
        CustomItemRouteProvider $routeProvider,
        FlashBag $flashBag
    ) {
        $this->requestStack           = $requestStack;
        $this->customItemModel        = $customItemModel;
        $this->sessionProviderFactory = $sessionProviderFactory;
        $this->permissionProvider     = $permissionProvider;
        $this->routeProvider          = $routeProvider;
        $this->flashBag               = $flashBag;
    }

    public function deleteAction(int $objectId): Response
    {
        $request  = $this->requestStack->getCurrentRequest();
        $itemIds  = json_decode($request->get('ids', '[]'), true);
        $page     = $this->sessionProviderFactory->createItemProvider($objectId)->getPage();
        $notFound = [];
        $denied   = [];
        $deleted  = [];

        foreach ($itemIds as $itemId) {
            try {
                $customItem = $this->customItemModel->fetchEntity((int) $itemId);
                $this->permissionProvider->canDelete($customItem);
                $this->customItemModel->delete($customItem);
                $deleted[] = $itemId;
            } catch (NotFoundException $e) {
                $notFound[] = $itemId;
            } catch (ForbiddenException $e) {
                $denied[] = $itemId;
            }
        }

        if ($deleted) {
            $this->flashBag->add(
                'mautic.core.notice.batch_deleted',
                ['%count%' => count($deleted)]
            );
        }

        if ($notFound) {
            $this->flashBag->add(
                'custom.item.error.items.not.found',
                ['%ids%' => implode(',', $notFound)],
                FlashBag::LEVEL_ERROR
            );
        }

        if ($denied) {
            $this->flashBag->add(
                'custom.item.error.items.denied',
                ['%ids%' => implode(',', $denied)],
                FlashBag::LEVEL_ERROR
            );
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
