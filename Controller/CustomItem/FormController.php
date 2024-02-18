<?php

declare(strict_types=1);

namespace MauticPlugin\CustomObjectsBundle\Controller\CustomItem;

use Mautic\CoreBundle\Controller\AbstractFormController;
use MauticPlugin\CustomObjectsBundle\Entity\CustomItem;
use MauticPlugin\CustomObjectsBundle\Exception\ForbiddenException;
use MauticPlugin\CustomObjectsBundle\Exception\NotFoundException;
use MauticPlugin\CustomObjectsBundle\Form\Type\CustomItemType;
use MauticPlugin\CustomObjectsBundle\Helper\LockFlashMessageHelper;
use MauticPlugin\CustomObjectsBundle\Model\CustomItemModel;
use MauticPlugin\CustomObjectsBundle\Model\CustomObjectModel;
use MauticPlugin\CustomObjectsBundle\Provider\CustomItemPermissionProvider;
use MauticPlugin\CustomObjectsBundle\Provider\CustomItemRouteProvider;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\Response;

class FormController extends AbstractFormController
{
    public function newAction(
        FormFactoryInterface $formFactory,
        CustomItemRouteProvider $routeProvider,
        CustomItemModel $customItemModel,
        CustomObjectModel $customObjectModel,
        CustomItemPermissionProvider $permissionProvider,
        int $objectId
    ): Response {
        try {
            $customItem = $this->performNewAction($customObjectModel, $customItemModel, $permissionProvider, $objectId);
        } catch (ForbiddenException $e) {
            return $this->accessDenied(false, $e->getMessage());
        }

        return $this->renderFormForItem(
            $formFactory,
            $routeProvider,
            $customItem,
            $routeProvider->buildNewRoute($objectId)
        );
    }

    public function newWithRedirectToContactAction(
        FormFactoryInterface $formFactory,
        CustomItemRouteProvider $routeProvider,
        CustomItemModel $customItemModel,
        CustomObjectModel $customObjectModel,
        CustomItemPermissionProvider $permissionProvider,
        int $objectId,
        int $contactId
    ): Response {
        try {
            $customItem = $this->performNewAction($customObjectModel, $customItemModel, $permissionProvider, $objectId);
        } catch (ForbiddenException $e) {
            return $this->accessDenied(false, $e->getMessage());
        }

        if ($customItem->getCustomObject()->getRelationshipObject()) {
            $customItem->setChildCustomItem(
                $customItemModel->populateCustomFields(
                    new CustomItem($customItem->getCustomObject()->getRelationshipObject())
                )
            );
        }

        return $this->renderFormForItem(
            $formFactory,
            $routeProvider,
            $customItem,
            $routeProvider->buildNewRouteWithRedirectToContact($objectId, $contactId),
            $contactId
        );
    }

    private function performNewAction(
        CustomObjectModel $customObjectModel,
        CustomItemModel $customItemModel,
        CustomItemPermissionProvider $permissionProvider,
        int $objectId
    ): CustomItem {
        $permissionProvider->canCreate($objectId);

        return $customItemModel->populateCustomFields(
            new CustomItem(
                $customObjectModel->fetchEntity($objectId)
            )
        );
    }

    public function editAction(
        FormFactoryInterface $formFactory,
        CustomItemRouteProvider $routeProvider,
        CustomItemModel $customItemModel,
        LockFlashMessageHelper $lockFlashMessageHelper,
        CustomItemPermissionProvider $permissionProvider,
        int $objectId,
        int $itemId
    ): Response {
        try {
            $customItem = $this->performEditAction($customItemModel, $permissionProvider, $itemId);
        } catch (NotFoundException $e) {
            return $this->notFound($e->getMessage());
        } catch (ForbiddenException $e) {
            return $this->accessDenied(false, $e->getMessage());
        }

        if ($customItemModel->isLocked($customItem)) {
            $lockFlashMessageHelper->addFlash(
                $customItem,
                $routeProvider->buildEditRoute($objectId, $itemId),
                $this->canEdit($customItem),
                'custom.item'
            );

            return $this->redirect($routeProvider->buildViewRoute($objectId, $itemId));
        }

        $customItemModel->lockEntity($customItem);

        return $this->renderFormForItem(
            $formFactory,
            $routeProvider,
            $customItem,
            $routeProvider->buildEditRoute($objectId, $itemId)
        );
    }

    public function editWithRedirectToContactAction(
        FormFactoryInterface $formFactory,
        CustomItemRouteProvider $routeProvider,
        CustomItemModel $customItemModel,
        LockFlashMessageHelper $lockFlashMessageHelper,
        CustomItemPermissionProvider $permissionProvider,
        int $objectId,
        int $itemId,
        int $contactId
    ): Response {
        try {
            $customItem = $this->performEditAction($customItemModel, $permissionProvider, $itemId);
        } catch (NotFoundException $e) {
            return $this->notFound($e->getMessage());
        } catch (ForbiddenException $e) {
            return $this->accessDenied(false, $e->getMessage());
        }

        if ($customItemModel->isLocked($customItem)) {
            $lockFlashMessageHelper->addFlash(
                $customItem,
                $routeProvider->buildEditRouteWithRedirectToContact($objectId, $itemId, $contactId),
                $this->canEdit($customItem),
                'custom.item'
            );

            return $this->redirect($routeProvider->buildViewRoute($objectId, $itemId));
        }

        if ($customItem->getCustomObject()->getRelationshipObject()) {
            $customItem->setChildCustomItem(
                $customItemModel->populateCustomFields(
                    $customItem->findChildCustomItem()
                )
            );
        }

        $customItemModel->lockEntity($customItem);

        return $this->renderFormForItem(
            $formFactory,
            $routeProvider,
            $customItem,
            $routeProvider->buildEditRouteWithRedirectToContact($objectId, $itemId, $contactId),
            $contactId
        );
    }

    public function cloneAction(
        FormFactoryInterface $formFactory,
        CustomItemRouteProvider $routeProvider,
        CustomItemModel $customItemModel,
        CustomItemPermissionProvider $permissionProvider,
        int $objectId,
        int $itemId
    ): Response {
        try {
            $customItem = clone $customItemModel->fetchEntity($itemId);
            $permissionProvider->canClone($customItem);
        } catch (NotFoundException $e) {
            return $this->notFound($e->getMessage());
        } catch (ForbiddenException $e) {
            return $this->accessDenied(false, $e->getMessage());
        }

        $customItem->setName($customItem->getName().' '.$this->translator->trans('mautic.core.form.clone'));

        return $this->renderFormForItem(
            $formFactory,
            $routeProvider,
            $customItem,
            $routeProvider->buildCloneRoute($objectId, $itemId)
        );
    }

    private function performEditAction(
        CustomItemModel $customItemModel,
        CustomItemPermissionProvider $permissionProvider,
        int $itemId
    ): CustomItem {
        $customItem = $customItemModel->fetchEntity($itemId);
        $permissionProvider->canEdit($customItem);

        return $customItem;
    }

    private function renderFormForItem(
        FormFactoryInterface $formFactory,
        CustomItemRouteProvider $routeProvider,
        CustomItem $customItem,
        string $route,
        ?int $contactId = null
    ): Response {
        $action  = $routeProvider->buildSaveRoute($customItem->getCustomObject()->getId(), $customItem->getId());
        $options = [
            'action'    => $action,
            'objectId'  => $customItem->getCustomObject()->getId(),
            'contactId' => $contactId,
            'cancelUrl' => 0 < $contactId ? $routeProvider->buildContactViewRoute($contactId) : null,
        ];

        $form = $formFactory->create(
            CustomItemType::class,
            $customItem,
            $options
        );

        return $this->delegateView(
            [
                'returnUrl'      => $routeProvider->buildListRoute($customItem->getCustomObject()->getId()),
                'viewParameters' => [
                    'entity'       => $customItem,
                    'customObject' => $customItem->getCustomObject(),
                    'form'         => $form->createView(),
                ],
                'contentTemplate' => '@CustomObjects/CustomItem/form.html.twig',
                'passthroughVars' => [
                    'mauticContent' => 'customItem',
                    'route'         => $route,
                ],
            ]
        );
    }
}
