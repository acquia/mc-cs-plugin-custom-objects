<?php

declare(strict_types=1);

/*
 * @copyright   2018 Mautic, Inc. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.com
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

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
        try {
            if ($itemId) {
                $customItem = $this->customItemModel->fetchEntity($itemId);
                $route      = $this->routeProvider->buildEditRoute($objectId, $itemId);
                $message    = 'mautic.core.notice.updated';
                $this->permissionProvider->canEdit($customItem);
            } else {
                $this->permissionProvider->canCreate($objectId);
                $customObject = $this->customObjectModel->fetchEntity($objectId);
                $message      = 'mautic.core.notice.created';
                $route        = $this->routeProvider->buildNewRoute($objectId);
                $customItem   = $this->customItemModel->populateCustomFields(new CustomItem($customObject));
            }
        } catch (NotFoundException $e) {
            return $this->notFound($e->getMessage());
        } catch (ForbiddenException $e) {
            return $this->accessDenied(false, $e->getMessage());
        }

        if ($this->customObjectModel->isLocked($customItem)) {
            $this->lockFlashMessageHelper->addFlash(
                $customItem,
                $this->routeProvider->buildEditRoute($objectId, $itemId),
                $this->canEdit($customItem),
                'custom.item'
            );

            return $this->redirect($this->routeProvider->buildViewRoute($objectId, $itemId));
        }

        $request = $this->requestStack->getCurrentRequest();
        $action  = $this->routeProvider->buildSaveRoute($objectId, $itemId);
        $form    = $this->formFactory->create(CustomItemType::class, $customItem, ['action' => $action, 'objectId' => $objectId]);
        $form->handleRequest($request);

        if ($form->isValid()) {
            $this->customItemModel->save($customItem);

            $this->flashBag->add(
                $message,
                [
                    '%name%' => $customItem->getName(),
                    '%url%'  => $this->routeProvider->buildEditRoute($objectId, $customItem->getId()),
                ]
            );

            $saveClicked = $form->get('buttons')->get('save')->isClicked();
            $contactId = (int)$form->get('contact_id')->getData();
            $detailView  = 'CustomObjectsBundle:CustomItem\View:view';
            $formView    = 'CustomObjectsBundle:CustomItem\Form:edit';

            if (0 < $contactId) {
                $this->customItemModel->linkEntity($customItem, 'contact', $contactId);
                if ($saveClicked) {
                    return $this->redirectToRoute('mautic_contact_action', ['objectAction' => 'view', 'objectId' => $contactId]);
                }
            }

            $request->setMethod(Request::METHOD_GET);

            if ($saveClicked) {
                $this->customItemModel->unlockEntity($customItem);
            }

            return $this->forward(
                $saveClicked ? $detailView : $formView,
                ['objectId' => $objectId, 'itemId' => $customItem->getId()]
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
