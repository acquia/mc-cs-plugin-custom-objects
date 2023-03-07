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
use Symfony\Component\Form\FormFactory;
use Symfony\Component\HttpFoundation\Response;

class FormController extends AbstractFormController
{
    /**
     * @var FormFactory
     */
    private $formFactory;

    /**
     * @var CustomItemModel
     */
    private $customItemModel;

    /**
     * @var CustomObjectModel
     */
    private $customObjectModel;

    /**
     * @var CustomItemPermissionProvider
     */
    private $permissionProvider;

    /**
     * @var CustomItemRouteProvider
     */
    private $routeProvider;

    /**
     * @var LockFlashMessageHelper
     */
    private $lockFlashMessageHelper;

    public function __construct(
        FormFactory $formFactory,
        CustomObjectModel $customObjectModel,
        CustomItemModel $customItemModel,
        CustomItemPermissionProvider $permissionProvider,
        CustomItemRouteProvider $routeProvider,
        LockFlashMessageHelper $lockFlashMessageHelper
    ) {
        $this->formFactory            = $formFactory;
        $this->customObjectModel      = $customObjectModel;
        $this->customItemModel        = $customItemModel;
        $this->permissionProvider     = $permissionProvider;
        $this->routeProvider          = $routeProvider;
        $this->lockFlashMessageHelper = $lockFlashMessageHelper;
    }

    public function newAction(int $objectId): Response
    {
        try {
            $customItem = $this->performNewAction($objectId);
        } catch (ForbiddenException $e) {
            return $this->accessDenied(false, $e->getMessage());
        }

        return $this->renderFormForItem($customItem, $this->routeProvider->buildNewRoute($objectId));
    }

    public function newWithRedirectToContactAction(int $objectId, int $contactId): Response
    {
        try {
            $customItem = $this->performNewAction($objectId);
        } catch (ForbiddenException $e) {
            return $this->accessDenied(false, $e->getMessage());
        }

        if ($customItem->getCustomObject()->getRelationshipObject()) {
            $customItem->setChildCustomItem(
                $this->customItemModel->populateCustomFields(
                    new CustomItem($customItem->getCustomObject()->getRelationshipObject())
                )
            );
        }

        return $this->renderFormForItem($customItem, $this->routeProvider->buildNewRouteWithRedirectToContact($objectId, $contactId), $contactId);
    }

    private function performNewAction(int $objectId): CustomItem
    {
        $this->permissionProvider->canCreate($objectId);

        return $this->customItemModel->populateCustomFields(
            new CustomItem(
                $this->customObjectModel->fetchEntity($objectId)
            )
        );
    }

    public function editAction(int $objectId, int $itemId): Response
    {
        try {
            $customItem = $this->performEditAction($itemId);
        } catch (NotFoundException $e) {
            return $this->notFound($e->getMessage());
        } catch (ForbiddenException $e) {
            return $this->accessDenied(false, $e->getMessage());
        }

        if ($this->customItemModel->isLocked($customItem)) {
            $this->lockFlashMessageHelper->addFlash(
                $customItem,
                $this->routeProvider->buildEditRoute($objectId, $itemId),
                $this->canEdit($customItem),
                'custom.item'
            );

            return $this->redirect($this->routeProvider->buildViewRoute($objectId, $itemId));
        }

        $this->customItemModel->lockEntity($customItem);

        return $this->renderFormForItem($customItem, $this->routeProvider->buildEditRoute($objectId, $itemId));
    }

    public function editWithRedirectToContactAction(int $objectId, int $itemId, int $contactId): Response
    {
        try {
            $customItem = $this->performEditAction($itemId);
        } catch (NotFoundException $e) {
            return $this->notFound($e->getMessage());
        } catch (ForbiddenException $e) {
            return $this->accessDenied(false, $e->getMessage());
        }

        if ($this->customItemModel->isLocked($customItem)) {
            $this->lockFlashMessageHelper->addFlash(
                $customItem,
                $this->routeProvider->buildEditRouteWithRedirectToContact($objectId, $itemId, $contactId),
                $this->canEdit($customItem),
                'custom.item'
            );

            return $this->redirect($this->routeProvider->buildViewRoute($objectId, $itemId));
        }

        if ($customItem->getCustomObject()->getRelationshipObject()) {
            $customItem->setChildCustomItem(
                $this->customItemModel->populateCustomFields(
                    $customItem->findChildCustomItem()
                )
            );
        }

        $this->customItemModel->lockEntity($customItem);

        return $this->renderFormForItem($customItem, $this->routeProvider->buildEditRouteWithRedirectToContact($objectId, $itemId, $contactId), $contactId);
    }

    private function performEditAction(int $itemId): CustomItem
    {
        $customItem = $this->customItemModel->fetchEntity($itemId);
        $this->permissionProvider->canEdit($customItem);

        return $customItem;
    }

    public function cloneAction(int $objectId, int $itemId): Response
    {
        try {
            $customItem = clone $this->customItemModel->fetchEntity($itemId);
            $this->permissionProvider->canClone($customItem);
        } catch (NotFoundException $e) {
            return $this->notFound($e->getMessage());
        } catch (ForbiddenException $e) {
            return $this->accessDenied(false, $e->getMessage());
        }

        $customItem->setName($customItem->getName().' '.$this->translator->trans('mautic.core.form.clone'));

        return $this->renderFormForItem($customItem, $this->routeProvider->buildCloneRoute($objectId, $itemId));
    }

    private function renderFormForItem(CustomItem $customItem, string $route, ?int $contactId = null): Response
    {
        $action  = $this->routeProvider->buildSaveRoute($customItem->getCustomObject()->getId(), $customItem->getId());
        $options = [
            'action'    => $action,
            'objectId'  => $customItem->getCustomObject()->getId(),
            'contactId' => $contactId,
            'cancelUrl' => 0 < $contactId ? $this->routeProvider->buildContactViewRoute($contactId) : null,
        ];

        $form = $this->formFactory->create(
            CustomItemType::class,
            $customItem,
            $options
        );

        return $this->delegateView(
            [
                'returnUrl'      => $this->routeProvider->buildListRoute($customItem->getCustomObject()->getId()),
                'viewParameters' => [
                    'entity'       => $customItem,
                    'customObject' => $customItem->getCustomObject(),
                    'form'         => $form->createView(),
                ],
                'contentTemplate' => 'CustomObjectsBundle:CustomItem:form.html.twig',
                'passthroughVars' => [
                    'mauticContent' => 'customItem',
                    'route'         => $route,
                ],
            ]
        );
    }
}
