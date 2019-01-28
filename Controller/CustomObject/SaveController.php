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

namespace MauticPlugin\CustomObjectsBundle\Controller\CustomObject;

use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Session;
use MauticPlugin\CustomObjectsBundle\Entity\CustomObject;
use MauticPlugin\CustomObjectsBundle\Form\Type\CustomObjectType;
use MauticPlugin\CustomObjectsBundle\Model\CustomObjectModel;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Form\FormFactory;
use Symfony\Component\Translation\TranslatorInterface;
use Symfony\Component\HttpFoundation\Request;
use Mautic\CoreBundle\Controller\CommonController;
use Symfony\Component\HttpFoundation\JsonResponse;
use MauticPlugin\CustomObjectsBundle\Exception\NotFoundException;
use MauticPlugin\CustomObjectsBundle\Provider\CustomObjectPermissionProvider;
use MauticPlugin\CustomObjectsBundle\Exception\ForbiddenException;
use MauticPlugin\CustomObjectsBundle\Provider\CustomObjectRouteProvider;

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
     * @var CustomObjectModel
     */
    private $customObjectModel;

    /**
     * @var CustomObjectPermissionProvider
     */
    private $permissionProvider;

    /**
     * @var CustomObjectRouteProvider
     */
    private $routeProvider;

    /**
     * @param RequestStack $requestStack
     * @param Session $session
     * @param FormFactory $formFactory
     * @param TranslatorInterface $translator
     * @param CustomObjectModel $customObjectModel
     * @param CustomObjectPermissionProvider $permissionProvider
     * @param CustomObjectRouteProvider $routeProvider
     */
    public function __construct(
        RequestStack $requestStack,
        Session $session,
        FormFactory $formFactory,
        TranslatorInterface $translator,
        CustomObjectModel $customObjectModel,
        CustomObjectPermissionProvider $permissionProvider,
        CustomObjectRouteProvider $routeProvider
    )
    {
        $this->requestStack       = $requestStack;
        $this->session            = $session;
        $this->formFactory        = $formFactory;
        $this->translator         = $translator;
        $this->customObjectModel  = $customObjectModel;
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
            $entity = $objectId ? $this->customObjectModel->fetchEntity($objectId): new CustomObject();
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
        $form    = $this->formFactory->create(CustomObjectType::class, $entity, ['action' => $action]);
        $form->handleRequest($request);
        
        if ($form->isValid()) {
            $this->customObjectModel->save($entity);

            $this->session->getFlashBag()->add(
                'notice',
                $this->translator->trans(
                    $objectId ? 'mautic.core.notice.updated' : 'mautic.core.notice.created',
                    [
                        '%name%' => $entity->getName(),
                        '%url%'  => $this->routeProvider->buildFormRoute($objectId),
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

        $route = $this->routeProvider->buildFormRoute($objectId);

        return $this->delegateView(
            [
                'returnUrl'      => $route,
                'viewParameters' => [
                    'entity' => $entity,
                    'form'   => $form->createView(),
                    'tmpl'   => $request->isXmlHttpRequest() ? $request->get('tmpl', 'index') : 'index',
                ],
                'contentTemplate' => 'CustomObjectsBundle:CustomObject:form.html.php',
                'passthroughVars' => [
                    'mauticContent' => 'customObject',
                    'route'         => $route,
                ],
            ]
        );
    }

    /**
     * @param Request               $request
     * @param CustomObject $entity
     * 
     * @return Response
     */
    private function redirectToEdit(Request $request, CustomObject $entity): Response
    {
        $request->setMethod('GET');
        $params = ['objectId' => $entity->getId()];

        return $this->forward('custom_object.form_controller:renderFormAction', $params);
    }

    /**
     * @param Request               $request
     * @param CustomObject $entity
     * 
     * @return Response
     */
    private function redirectToDetail(Request $request, CustomObject $entity): Response
    {
        $request->setMethod('GET');
        $params = ['objectId' => $entity->getId()];

        return $this->forward('CustomObjectsBundle:CustomObject\View:view', $params);
    }
}