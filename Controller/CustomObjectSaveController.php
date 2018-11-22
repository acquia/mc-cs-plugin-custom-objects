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

namespace MauticPlugin\CustomObjectsBundle\Controller;

use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Session;
use MauticPlugin\CustomObjectsBundle\Entity\CustomObject;
use MauticPlugin\CustomObjectsBundle\Form\Type\CustomObjectType;
use MauticPlugin\CustomObjectsBundle\Model\CustomObjectModel;
use Symfony\Bundle\FrameworkBundle\Routing\Router;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Form\FormFactory;
use Symfony\Component\Translation\TranslatorInterface;
use Symfony\Component\HttpFoundation\Request;
use Mautic\CoreBundle\Controller\CommonController;
use Symfony\Component\HttpFoundation\JsonResponse;
use MauticPlugin\CustomObjectsBundle\Exception\NotFoundException;
use MauticPlugin\CustomObjectsBundle\Provider\CustomObjectPermissionProvider;
use MauticPlugin\CustomObjectsBundle\Exception\ForbiddenException;

class CustomObjectSaveController extends CommonController
{
    /**
     * @var RequestStack
     */
    private $requestStack;

    /**
     * @var Router
     */
    private $router;

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
     * @param RequestStack $requestStack
     * @param Router $router
     * @param Session $session
     * @param FormFactory $formFactory
     * @param TranslatorInterface $translator
     * @param CustomObjectModel $customObjectModel
     * @param CustomObjectPermissionProvider $permissionProvider
     */
    public function __construct(
        RequestStack $requestStack,
        Router $router,
        Session $session,
        FormFactory $formFactory,
        TranslatorInterface $translator,
        CustomObjectModel $customObjectModel,
        CustomObjectPermissionProvider $permissionProvider
    )
    {
        $this->requestStack       = $requestStack;
        $this->router             = $router;
        $this->session            = $session;
        $this->formFactory        = $formFactory;
        $this->translator         = $translator;
        $this->customObjectModel  = $customObjectModel;
        $this->permissionProvider = $permissionProvider;
    }

    /**
     * @param int|null $objectId
     * 
     * @return Response|JsonResponse
     */
    public function saveAction(?int $objectId = null)
    {
        try {
            $entity = $objectId ? $this->customObjectModel->getEntity($objectId): new CustomObject();
        } catch (NotFoundException $e) {
            return $this->notFound($e->getMessage());
        }

        try {
            if ($entity->isNew()) {
                $this->permissionProvider->canCreate();
            } else {
                $this->permissionProvider->canEdit($entity);
            }
        } catch (ForbiddenException $e) {
            return $this->accessDenied($e->getMessage());
        }

        $request = $this->requestStack->getCurrentRequest();
        $action  = $this->router->generate('mautic_custom_object_save', ['objectId' => $objectId]);
        $form    = $this->formFactory->create(CustomObjectType::class, $entity, ['action' => $action]);
        $form->handleRequest($request);

        // $validator = $this->get('validator');
        // $errors = $validator->validate($entity);
        
        if ($form->isValid()) {
            $this->customObjectModel->save($entity);

            $this->session->getFlashBag()->add(
                'notice',
                $this->translator->trans(
                    $objectId ? 'mautic.core.notice.updated' : 'mautic.core.notice.created',
                    [
                        '%name%' => $entity->getName(),
                        '%url%'  => $this->router->generate(
                            'mautic_custom_object_edit',
                            ['objectId' => $entity->getId()]
                        ),
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
                'returnUrl'      => $this->router->generate('mautic_custom_object_new'),
                'viewParameters' => [
                    'entity' => $entity,
                    'form'   => $form->createView(),
                    'tmpl'   => $request->isXmlHttpRequest() ? $request->get('tmpl', 'index') : 'index',
                ],
                'contentTemplate' => 'CustomObjectsBundle:CustomObject:form.html.php',
                'passthroughVars' => [
                    'mauticContent' => 'customObject',
                    'route'         => $this->router->generate('mautic_custom_object_new'),
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

        return $this->forward('custom_object.edit_controller:renderFormAction', $params);
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

        return $this->forward('CustomObjectsBundle:CustomObjectView:view', $params);
    }
}