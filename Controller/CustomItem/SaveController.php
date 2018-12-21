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
     * @param CustomItemPermissionProvider $permissionProvider
     * @param CustomItemRouteProvider $routeProvider
     */
    public function __construct(
        RequestStack $requestStack,
        Session $session,
        FormFactory $formFactory,
        TranslatorInterface $translator,
        CustomItemModel $customItemModel,
        CustomItemPermissionProvider $permissionProvider,
        CustomItemRouteProvider $routeProvider
    )
    {
        $this->requestStack       = $requestStack;
        $this->session            = $session;
        $this->formFactory        = $formFactory;
        $this->translator         = $translator;
        $this->customItemModel  = $customItemModel;
        $this->permissionProvider = $permissionProvider;
        $this->routeProvider      = $routeProvider;
    }

    /**
     * @param int|null $objectId
     * 
     * @return Response|JsonResponse
     */
    public function saveAction(?int $objectId = null)
    {
        try {
            $entity = $objectId ? $this->customItemModel->getEntity($objectId): new CustomItem();
            if ($entity->isNew()) {
                $this->permissionProvider->canCreate();
            } else {
                $this->permissionProvider->canEdit($entity);
            }
        } catch (NotFoundException $e) {
            return $this->notFound($e->getMessage());
        } catch (ForbiddenException $e) {
            $this->accessDenied(false, $e->getMessage());
        }

        $request = $this->requestStack->getCurrentRequest();
        $action  = $this->routeProvider->buildSaveRoute($objectId);
        $form    = $this->formFactory->create(CustomItemType::class, $entity, ['action' => $action]);
        $form->handleRequest($request);
        
        if ($form->isValid()) {
            $this->customItemModel->save($entity);

            $this->session->getFlashBag()->add(
                'notice',
                $this->translator->trans(
                    $objectId ? 'mautic.core.notice.updated' : 'mautic.core.notice.created',
                    [
                        '%name%' => $entity->getName(),
                        '%url%'  => $this->routeProvider->buildEditRoute($objectId),
                    ], 
                    'flashes'
                )
            );

            if ($form->get('buttons')->get('save')->isClicked()) {
                return $this->redirectToDetail($request, $entity);
            } else {
                return $this->redirectToEdit($request, $entity);
            }
        }

        return $this->delegateView(
            [
                'returnUrl'      => $this->routeProvider->buildNewRoute(),
                'viewParameters' => [
                    'entity' => $entity,
                    'form'   => $form->createView(),
                    'tmpl'   => $request->isXmlHttpRequest() ? $request->get('tmpl', 'index') : 'index',
                ],
                'contentTemplate' => 'CustomObjectsBundle:CustomItem:form.html.php',
                'passthroughVars' => [
                    'mauticContent' => 'customItem',
                    'route'         => $this->routeProvider->buildNewRoute(),
                ],
            ]
        );
    }

    /**
     * @param Request    $request
     * @param CustomItem $entity
     * 
     * @return Response
     */
    private function redirectToEdit(Request $request, CustomItem $entity): Response
    {
        $request->setMethod('GET');
        $params = ['objectId' => $entity->getId()];

        return $this->forward('custom_item.edit_controller:renderFormAction', $params);
    }

    /**
     * @param Request    $request
     * @param CustomItem $entity
     * 
     * @return Response
     */
    private function redirectToDetail(Request $request, CustomItem $entity): Response
    {
        $request->setMethod('GET');
        $params = ['objectId' => $entity->getId()];

        return $this->forward('CustomObjectsBundle:CustomItem\View:view', $params);
    }
}