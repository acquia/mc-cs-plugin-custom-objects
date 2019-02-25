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
use Symfony\Component\HttpFoundation\Session\Session;
use MauticPlugin\CustomObjectsBundle\Entity\CustomItem;
use MauticPlugin\CustomObjectsBundle\Form\Type\CustomItemType;
use MauticPlugin\CustomObjectsBundle\Model\CustomItemModel;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Form\FormFactory;
use Symfony\Component\Translation\TranslatorInterface;
use Symfony\Component\HttpFoundation\Request;
use Mautic\CoreBundle\Controller\CommonController;
use Symfony\Component\HttpFoundation\JsonResponse;
use MauticPlugin\CustomObjectsBundle\Exception\NotFoundException;
use MauticPlugin\CustomObjectsBundle\Provider\CustomItemPermissionProvider;
use MauticPlugin\CustomObjectsBundle\Exception\ForbiddenException;
use MauticPlugin\CustomObjectsBundle\Provider\CustomItemRouteProvider;
use MauticPlugin\CustomObjectsBundle\Model\CustomObjectModel;

class SaveController extends CommonController
{
    /**
     * @var RequestStack
     */
    private $requestStack;

    /**
     * @var Session
     */
    private $session;

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
     * @param RequestStack $requestStack
     * @param Session $session
     * @param FormFactory $formFactory
     * @param TranslatorInterface $translator
     * @param CustomItemModel $customItemModel
     * @param CustomObjectModel $customObjectModel
     * @param CustomItemPermissionProvider $permissionProvider
     * @param CustomItemRouteProvider $routeProvider
     */
    public function __construct(
        RequestStack $requestStack,
        Session $session,
        FormFactory $formFactory,
        TranslatorInterface $translator,
        CustomItemModel $customItemModel,
        CustomObjectModel $customObjectModel,
        CustomItemPermissionProvider $permissionProvider,
        CustomItemRouteProvider $routeProvider
    )
    {
        $this->requestStack       = $requestStack;
        $this->session            = $session;
        $this->formFactory        = $formFactory;
        $this->translator         = $translator;
        $this->customItemModel    = $customItemModel;
        $this->customObjectModel  = $customObjectModel;
        $this->permissionProvider = $permissionProvider;
        $this->routeProvider      = $routeProvider;
    }

    /**
     * @param int      $objectId
     * @param int|null $itemId
     * 
     * @return Response|JsonResponse
     */
    public function saveAction(int $objectId, ?int $itemId = null)
    {
        try {
            $customObject = $this->customObjectModel->fetchEntity($objectId);

            if ($itemId) {
                $customItem = $this->customItemModel->fetchEntity($itemId);
                $this->permissionProvider->canEdit($customItem);
            } else {
                $this->permissionProvider->canCreate($objectId);
                $customItem = $this->customItemModel->populateCustomFields(new CustomItem($customObject));
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

            $this->session->getFlashBag()->add(
                'notice',
                $this->translator->trans(
                    $itemId ? 'mautic.core.notice.updated' : 'mautic.core.notice.created',
                    [
                        '%name%' => $customItem->getName(),
                        '%url%'  => $this->routeProvider->buildEditRoute($objectId, $customItem->getId()),
                    ], 
                    'flashes'
                )
            );

            if ($form->get('buttons')->get('save')->isClicked()) {
                $where = 'CustomObjectsBundle:CustomItem\View:view';
            } else {
                $where = 'CustomObjectsBundle:CustomItem\Form:renderForm';
            }

            return $this->redirectTo($where, $request, $customItem);
        }

        $route = $itemId ? $this->routeProvider->buildEditRoute($objectId, $itemId) : $this->routeProvider->buildNewRoute($objectId);

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

    /**
     * @param string     $where
     * @param Request    $request
     * @param CustomItem $customItem
     * 
     * @return Response
     */
    private function redirectTo(string $where, Request $request, CustomItem $customItem): Response
    {
        $request->setMethod('GET');
        $params = ['objectId' => $customItem->getCustomObject()->getId(), 'itemId' => $customItem->getId()];

        return $this->forward($where, $params);
    }
}