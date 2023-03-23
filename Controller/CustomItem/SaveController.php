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
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;

class SaveController extends AbstractFormController
{
    /**
     * @var RequestStack
     */
    private $requestStack;

    /**
     * @var FormFactoryInterface
     */
    private $formFactory;

    /**
     * @var FlashBag
     */
    private $flashBag;

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
        RequestStack $requestStack,
        FormFactoryInterface $formFactory,
        FlashBag $flashBag,
        CustomItemModel $customItemModel,
        CustomObjectModel $customObjectModel,
        CustomItemPermissionProvider $permissionProvider,
        CustomItemRouteProvider $routeProvider,
        LockFlashMessageHelper $lockFlashMessageHelper
    ) {
        $this->requestStack           = $requestStack;
        $this->formFactory            = $formFactory;
        $this->flashBag               = $flashBag;
        $this->customItemModel        = $customItemModel;
        $this->customObjectModel      = $customObjectModel;
        $this->permissionProvider     = $permissionProvider;
        $this->routeProvider          = $routeProvider;
        $this->lockFlashMessageHelper = $lockFlashMessageHelper;
    }

    public function saveAction(int $objectId, ?int $itemId = null): Response
    {
        $request        = $this->requestStack->getCurrentRequest();
        $customItemData = $request->request->get('custom_item');
        $contactId      = intval($customItemData['contact_id'] ?? 0);

        try {
            if ($itemId) {
                $message    = 'mautic.core.notice.updated';
                $customItem = $this->customItemModel->fetchEntity($itemId);
                $route      = 0 < $contactId
                    ? $this->routeProvider->buildEditRouteWithRedirectToContact($objectId, $itemId, $contactId)
                    : $this->routeProvider->buildEditRoute($objectId, $itemId);
                $this->permissionProvider->canEdit($customItem);
            } else {
                $this->permissionProvider->canCreate($objectId);
                $message      = 'mautic.core.notice.created';
                $customObject = $this->customObjectModel->fetchEntity($objectId);
                $customItem   = $this->customItemModel->populateCustomFields(new CustomItem($customObject));
                $route        = 0 < $contactId
                    ? $this->routeProvider->buildNewRouteWithRedirectToContact($objectId, $contactId)
                    : $this->routeProvider->buildNewRoute($objectId);
            }
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

        $action = $this->routeProvider->buildSaveRoute($objectId, $itemId);

        if (!$customItem->getId() && $customItem->getCustomObject()->getRelationshipObject() && $contactId) {
            $customItem->setChildCustomItem(
                $this->customItemModel->populateCustomFields(
                    new CustomItem($customItem->getCustomObject()->getRelationshipObject())
                )
            );
        }
        $form = $this->formFactory->create(CustomItemType::class, $customItem, ['action' => $action, 'objectId' => $objectId]);

        $form->handleRequest($request);

        if ($form->isValid()) {
            $customItem = $this->customItemModel->save($customItem);

            if ($customItem->getChildCustomItem()) {
                $customItem->getChildCustomItem()->generateNameForChildObject('contact', $contactId, $customItem);
                $customItem = $this->customItemModel->save($customItem->getChildCustomItem());
            }

            if ($customItem->hasBeenUpdated()) {
                $message = 'custom.item.notice.merged';
            }

            $this->flashBag->add(
                $message,
                [
                    '%name%' => $customItem->getName(),
                    '%url%'  => $this->routeProvider->buildEditRoute($objectId, $customItem->getId()),
                ]
            );

            $saveClicked = $form->get('buttons')->get('save')->isClicked();
            $detailView  = 'CustomObjectsBundle:CustomItem\View:view';
            $formView    = 'CustomObjectsBundle:CustomItem\Form:edit';

            $pathParameters = [
                'objectId' => $objectId,
                'itemId'   => $customItem->getId(),
            ];

            if (0 < $contactId) {
                // For parent-child items we want to link both items together and the child item with the contact.
                if ($customItem->getChildCustomItem()) {
                    $this->customItemModel->linkEntity($customItem->getChildCustomItem(), 'contact', $contactId);
                    $this->customItemModel->linkEntity($customItem->getChildCustomItem(), 'customItem', $customItem->getId());
                }

                // For parent items we want to connect the parent item directly to the contact.
                $this->customItemModel->linkEntity($customItem, 'contact', $contactId);

                if ($saveClicked) {
                    return $this->redirectToRoute('mautic_contact_action', ['objectAction' => 'view', 'objectId' => $contactId]);
                }

                $formView                    = 'CustomObjectsBundle:CustomItem\Form:editWithRedirectToContact';
                $pathParameters['contactId'] = $contactId;
            }

            $request->setMethod(Request::METHOD_GET);

            if ($saveClicked) {
                $this->customItemModel->unlockEntity($customItem);
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
                'contentTemplate' => 'CustomObjectsBundle:CustomItem:form.html.php',
                'passthroughVars' => [
                    'mauticContent' => 'customItem',
                    'route'         => $route,
                ],
            ]
        );
    }
}
