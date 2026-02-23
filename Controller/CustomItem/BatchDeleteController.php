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
use Doctrine\Persistence\ManagerRegistry;
use Mautic\CoreBundle\Factory\ModelFactory;
use Mautic\CoreBundle\Factory\MauticFactory;
use Mautic\CoreBundle\Helper\UserHelper;
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\CoreBundle\Translation\Translator;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Mautic\CoreBundle\Security\Permissions\CorePermissions;

class BatchDeleteController extends CommonController
{
    public function __construct(
        ManagerRegistry $doctrine,
        MauticFactory $mauticFactory,
        ModelFactory $modelFactory,
        UserHelper $userHelper,
        CoreParametersHelper $coreParametersHelper,
        EventDispatcherInterface $dispatcher,
        Translator $translator,
        private FlashBag $flashBag,
        private RequestStack $requestStack,
        CorePermissions $security,
        private CustomItemPermissionProvider $permissionProvider,
        private CustomItemRouteProvider $routeProvider,
        private CustomItemModel $customItemModel,
        private SessionProviderFactory $sessionProviderFactory,
    ) {
        parent::__construct($doctrine, $mauticFactory, $modelFactory, $userHelper, $coreParametersHelper, $dispatcher, $translator, $flashBag, $requestStack, $security);
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
