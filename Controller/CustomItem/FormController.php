<?php

declare(strict_types=1);

namespace MauticPlugin\CustomObjectsBundle\Controller\CustomItem;

use Mautic\CoreBundle\Service\FlashBag;
use Mautic\CoreBundle\Helper\UserHelper;
use Doctrine\Persistence\ManagerRegistry;
use Mautic\CoreBundle\Factory\ModelFactory;
use Mautic\CoreBundle\Translation\Translator;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Form\FormFactoryInterface;
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Symfony\Component\HttpFoundation\RequestStack;
use MauticPlugin\CustomObjectsBundle\Entity\CustomItem;
use Mautic\CoreBundle\Controller\AbstractFormController;
use Mautic\CoreBundle\Security\Permissions\CorePermissions;
use MauticPlugin\CustomObjectsBundle\Model\CustomItemModel;
use MauticPlugin\CustomObjectsBundle\Model\CustomObjectModel;
use MauticPlugin\CustomObjectsBundle\Form\Type\CustomItemType;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use MauticPlugin\CustomObjectsBundle\Exception\NotFoundException;
use MauticPlugin\CustomObjectsBundle\Exception\ForbiddenException;
use MauticPlugin\CustomObjectsBundle\Helper\LockFlashMessageHelper;
use MauticPlugin\CustomObjectsBundle\Provider\CustomItemRouteProvider;
use MauticPlugin\CustomObjectsBundle\Provider\CustomItemPermissionProvider;

class FormController extends AbstractFormController
{
    public function __construct(
        ManagerRegistry $doctrine,
        ModelFactory $modelFactory,
        UserHelper $userHelper,
        CoreParametersHelper $coreParametersHelper,
        EventDispatcherInterface $dispatcher,
        Translator $translator,
        FlashBag $flashBag,
        RequestStack $requestStack,
        CorePermissions $security,
        private FormFactoryInterface $formFactory,
        private CustomObjectModel $customObjectModel,
        private CustomItemModel $customItemModel,
        private CustomItemPermissionProvider $permissionProvider,
        private CustomItemRouteProvider $routeProvider,
        private LockFlashMessageHelper $lockFlashMessageHelper,
    ) {
        parent::__construct($doctrine, $modelFactory, $userHelper, $coreParametersHelper, $dispatcher, $translator, $flashBag, $requestStack, $security);
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
                'contentTemplate' => '@CustomObjects/CustomItem/form.html.twig',
                'passthroughVars' => [
                    'mauticContent' => 'customItem',
                    'route'         => $route,
                ],
            ]
        );
    }
}
