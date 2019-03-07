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

use Symfony\Component\HttpFoundation\RequestStack;
use MauticPlugin\CustomObjectsBundle\Entity\CustomItem;
use MauticPlugin\CustomObjectsBundle\Form\Type\CustomItemType;
use MauticPlugin\CustomObjectsBundle\Model\CustomItemModel;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Form\FormFactoryInterface;
use Mautic\CoreBundle\Controller\CommonController;
use MauticPlugin\CustomObjectsBundle\Exception\NotFoundException;
use MauticPlugin\CustomObjectsBundle\Provider\CustomItemPermissionProvider;
use MauticPlugin\CustomObjectsBundle\Exception\ForbiddenException;
use MauticPlugin\CustomObjectsBundle\Provider\CustomItemRouteProvider;
use MauticPlugin\CustomObjectsBundle\Model\CustomObjectModel;
use Mautic\CoreBundle\Service\FlashBag;
use Symfony\Component\HttpFoundation\Request;

class SaveController extends CommonController
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
     * @param RequestStack                 $requestStack
     * @param FormFactoryInterface         $formFactory
     * @param FlashBag                     $flashBag
     * @param CustomItemModel              $customItemModel
     * @param CustomObjectModel            $customObjectModel
     * @param CustomItemPermissionProvider $permissionProvider
     * @param CustomItemRouteProvider      $routeProvider
     */
    public function __construct(
        RequestStack $requestStack,
        FormFactoryInterface $formFactory,
        FlashBag $flashBag,
        CustomItemModel $customItemModel,
        CustomObjectModel $customObjectModel,
        CustomItemPermissionProvider $permissionProvider,
        CustomItemRouteProvider $routeProvider
    ) {
        $this->requestStack       = $requestStack;
        $this->formFactory        = $formFactory;
        $this->flashBag           = $flashBag;
        $this->customItemModel    = $customItemModel;
        $this->customObjectModel  = $customObjectModel;
        $this->permissionProvider = $permissionProvider;
        $this->routeProvider      = $routeProvider;
    }

    /**
     * @param int      $objectId
     * @param int|null $itemId
     *
     * @return Response
     */
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
            $detailView  = 'CustomObjectsBundle:CustomItem\View:view';
            $formView    = 'CustomObjectsBundle:CustomItem\Form:renderForm';

            $request->setMethod(Request::METHOD_GET);

            return $this->forward(
                $saveClicked ? $detailView : $formView,
                ['objectId' => $objectId, 'itemId' => $customItem->getId()]
            );
        }

        return $this->delegateView(
            [
                'returnUrl'      => $route,
                'viewParameters' => [
                    'entity' => $customItem,
                    'form'   => $form->createView(),
                    'tmpl'   => $request->isXmlHttpRequest() ? $request->get('tmpl', 'index') : 'index',
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
