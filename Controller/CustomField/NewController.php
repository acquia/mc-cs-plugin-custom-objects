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

namespace MauticPlugin\CustomObjectsBundle\Controller\CustomField;

use MauticPlugin\CustomObjectsBundle\Entity\CustomFieldFactory;
use MauticPlugin\CustomObjectsBundle\Exception\NotFoundException;
use MauticPlugin\CustomObjectsBundle\Form\Type\CustomFieldType;
use MauticPlugin\CustomObjectsBundle\Model\CustomObjectModel;
use MauticPlugin\CustomObjectsBundle\Repository\CustomObjectRepository;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Form\FormFactory;
use Symfony\Component\HttpFoundation\Request;
use Mautic\CoreBundle\Controller\CommonController;
use Symfony\Component\HttpFoundation\JsonResponse;
use MauticPlugin\CustomObjectsBundle\Provider\CustomFieldPermissionProvider;
use MauticPlugin\CustomObjectsBundle\Exception\ForbiddenException;
use MauticPlugin\CustomObjectsBundle\Provider\CustomFieldRouteProvider;

class NewController extends CommonController
{
    /**
     * @var FormFactory
     */
    private $formFactory;

    /**
     * @var CustomFieldPermissionProvider
     */
    private $permissionProvider;

    /**
     * @var CustomFieldRouteProvider
     */
    private $routeProvider;

    /**
     * @var CustomObjectRepository
     */
    private $customObjectRepository;

    /**
     * @var CustomObjectModel
     */
    private $customObjectModel;

    /**
     * @var CustomFieldFactory
     */
    private $customFieldFactory;

    /**
     * @param FormFactory                   $formFactory
     * @param CustomFieldPermissionProvider $permissionProvider
     * @param CustomFieldRouteProvider      $routeProvider
     * @param CustomObjectRepository        $customObjectRepository
     * @param CustomObjectModel             $customObjectModel
     * @param CustomFieldFactory            $customFieldFactory
     */
    public function __construct(
        FormFactory $formFactory,
        CustomFieldPermissionProvider $permissionProvider,
        CustomFieldRouteProvider $routeProvider,
        CustomObjectRepository $customObjectRepository,
        CustomObjectModel $customObjectModel,
        CustomFieldFactory $customFieldFactory
    )
    {
        $this->formFactory        = $formFactory;
        $this->permissionProvider = $permissionProvider;
        $this->routeProvider      = $routeProvider;
        $this->customObjectRepository = $customObjectRepository;
        $this->customObjectModel = $customObjectModel;
        $this->customFieldFactory = $customFieldFactory;
    }

    /**
     * @param Request $request
     *
     * @return Response|JsonResponse
     */
    public function renderFormAction(Request $request)
    {
        try {
            $this->permissionProvider->canCreate();
        } catch (ForbiddenException $e) {
            $this->accessDenied(false, $e->getMessage());
        }

        try {
            $customObject = $this->customObjectModel->fetchEntity((int) $request->get('objectId'));
        } catch (NotFoundException $e) {
            return $this->notFound($e->getMessage());
        }

        $success = 0;

        $field = $this->customFieldFactory->create($request->get('fieldType'), $customObject);

        $route = $this->routeProvider->buildNewRoute($customObject, $field->getType()->getKey());

        $action = $this->routeProvider->buildSaveRoute();
        $form   = $this->formFactory->create(CustomFieldType::class, $field, ['action' => $action]);

        if ($this->request->getMethod() === Request::METHOD_POST) {
            $form->handleRequest($request);
            if ($form->isSubmitted() && $form->isValid()) {
                $this->customObjectRepository->saveEntity($form->getData());
                $success = 1;
                $route = '';
            }
        }

        return $this->delegateView(
            [
                'returnUrl'      => $this->routeProvider->buildListRoute(),
                'viewParameters' => [
                    'customObject' => $customObject,
                    'customField' => $field,
                    'form'   => $form->createView(),
                ],
                'contentTemplate' => 'CustomObjectsBundle:CustomField:form.html.php',
                'passthroughVars' => [
                    'mauticContent' => 'customField',
                    'route'         => $route,
                    'closeModal'    => $success,
                ],
            ]
        );
    }
}