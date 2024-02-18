<?php

declare(strict_types=1);

namespace MauticPlugin\CustomObjectsBundle\Controller\CustomItem;

use Mautic\CoreBundle\Controller\AbstractFormController;
use Mautic\CoreBundle\Service\FlashBag;
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
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class SaveController extends AbstractFormController
{
    public function saveAction(
        Request $request,
        FormFactoryInterface $formFactory,
        FlashBag $flashBag,
        CustomItemModel $customItemModel,
        CustomObjectModel $customObjectModel,
        CustomItemPermissionProvider $permissionProvider,
        CustomItemRouteProvider $routeProvider,
        LockFlashMessageHelper $lockFlashMessageHelper,
        int $objectId,
        ?int $itemId = null
    ): Response {
        $customItemData = $request->request->get('custom_item');
        $contactId      = intval($customItemData['contact_id'] ?? 0);

        try {
            if ($itemId) {
                $message    = 'mautic.core.notice.updated';
                $customItem = $customItemModel->fetchEntity($itemId);
                $route      = 0 < $contactId
                    ? $routeProvider->buildEditRouteWithRedirectToContact($objectId, $itemId, $contactId)
                    : $routeProvider->buildEditRoute($objectId, $itemId);
                $permissionProvider->canEdit($customItem);
            } else {
                $permissionProvider->canCreate($objectId);
                $message      = 'mautic.core.notice.created';
                $customObject = $customObjectModel->fetchEntity($objectId);
                $customItem   = $customItemModel->populateCustomFields(new CustomItem($customObject));
                $route        = 0 < $contactId
                    ? $routeProvider->buildNewRouteWithRedirectToContact($objectId, $contactId)
                    : $routeProvider->buildNewRoute($objectId);
            }
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

        $action = $routeProvider->buildSaveRoute($objectId, $itemId);

        if (!$customItem->getId() && $customItem->getCustomObject()->getRelationshipObject() && $contactId) {
            $customItem->setChildCustomItem(
                $customItemModel->populateCustomFields(
                    new CustomItem($customItem->getCustomObject()->getRelationshipObject())
                )
            );
        }
        $form = $formFactory->create(CustomItemType::class, $customItem, ['action' => $action, 'objectId' => $objectId]);

        $form->handleRequest($request);

        if ($form->isValid()) {
            $customItem = $customItemModel->save($customItem);

            if ($customItem->getChildCustomItem()) {
                $customItem->getChildCustomItem()->generateNameForChildObject('contact', $contactId, $customItem);
                $customItem = $customItemModel->save($customItem->getChildCustomItem());
            }

            if($customItem->hasBeenUpdated()){
                $message = 'custom.item.notice.merged';
            }

            $flashBag->add(
                $message,
                [
                    '%name%' => $customItem->getName(),
                    '%url%'  => $routeProvider->buildEditRoute($objectId, $customItem->getId()),
                ]
            );

            $saveClicked = $form->get('buttons')->get('save')->isClicked();
            $detailView  = 'MauticPlugin\CustomObjectsBundle\Controller\CustomItem\ViewController:viewAction';
            $formView    = 'MauticPlugin\CustomObjectsBundle\Controller\CustomItem\FormController:editAction';

            $pathParameters = [
                'objectId' => $objectId,
                'itemId'   => $customItem->getId(),
            ];

            if (0 < $contactId) {
                // For parent-child items we want to link both items together and the child item with the contact.
                if ($customItem->getChildCustomItem()) {
                    $customItemModel->linkEntity($customItem->getChildCustomItem(), 'contact', $contactId);
                    $customItemModel->linkEntity($customItem->getChildCustomItem(), 'customItem', $customItem->getId());
                }

                // For parent items we want to connect the parent item directly to the contact.
                $customItemModel->linkEntity($customItem, 'contact', $contactId);

                if ($saveClicked) {
                    return $this->redirectToRoute('mautic_contact_action', ['objectAction' => 'view', 'objectId' => $contactId]);
                }

                $formView                    = 'MauticPlugin\CustomObjectsBundle\Controller\CustomItem\FormController:editWithRedirectToContactAction';
                $pathParameters['contactId'] = $contactId;
            }

            $request->setMethod(Request::METHOD_GET);

            if ($saveClicked) {
                $customItemModel->unlockEntity($customItem);
            }

            return $this->forward(
                $saveClicked ? $detailView : $formView,
                $pathParameters
            );
        }

        return $this->delegateView(
            [
                'returnUrl'      => $route,
                'viewParameters' => [
                    'entity'       => $customItem,
                    'customObject' => $customItem->getCustomObject(),
                    'form'         => $form->createView(),
                    'tmpl'         => $request->isXmlHttpRequest() ? $request->get('tmpl', 'index') : 'index',
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
