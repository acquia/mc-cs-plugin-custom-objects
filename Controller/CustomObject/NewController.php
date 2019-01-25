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

use MauticPlugin\CustomObjectsBundle\Entity\CustomObject;
use MauticPlugin\CustomObjectsBundle\Form\Type\CustomObjectType;
use MauticPlugin\CustomObjectsBundle\Provider\CustomFieldTypeProvider;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Form\FormFactory;
use Symfony\Component\HttpFoundation\Request;
use Mautic\CoreBundle\Controller\CommonController;
use Symfony\Component\HttpFoundation\JsonResponse;
use MauticPlugin\CustomObjectsBundle\Provider\CustomObjectPermissionProvider;
use MauticPlugin\CustomObjectsBundle\Exception\ForbiddenException;
use MauticPlugin\CustomObjectsBundle\Provider\CustomObjectRouteProvider;

class NewController extends CommonController
{
    /**
     * @var FormFactory
     */
    private $formFactory;

    /**
     * @var CustomObjectPermissionProvider
     */
    private $permissionProvider;

    /**
     * @var CustomObjectRouteProvider
     */
    private $routeProvider;

    /**
     * @var CustomFieldTypeProvider
     */
    private $customFieldTypeProvider;

    /**
     * @param FormFactory                    $formFactory
     * @param CustomObjectPermissionProvider $permissionProvider
     * @param CustomObjectRouteProvider      $routeProvider
     * @param CustomFieldTypeProvider        $customFieldTypeProvider
     */
    public function __construct(
        FormFactory $formFactory,
        CustomObjectPermissionProvider $permissionProvider,
        CustomObjectRouteProvider $routeProvider,
        CustomFieldTypeProvider $customFieldTypeProvider
    )
    {
        $this->formFactory        = $formFactory;
        $this->permissionProvider = $permissionProvider;
        $this->routeProvider      = $routeProvider;
        $this->customFieldTypeProvider = $customFieldTypeProvider;
    }

    /**
     * @return Response|JsonResponse
     */
    public function renderFormAction()
    {
        try {
            $this->permissionProvider->canCreate();
        } catch (ForbiddenException $e) {
            $this->accessDenied(false, $e->getMessage());
        }
        
        $customObject  = new CustomObject();
        $action  = $this->routeProvider->buildSaveRoute();
        $form    = $this->formFactory->create(CustomObjectType::class, $customObject, ['action' => $action]);

        $availableFieldTypes = $this->customFieldTypeProvider->getTypes();

        return $this->delegateView(
            [
                'returnUrl'      => $this->routeProvider->buildListRoute(),
                'viewParameters' => [
                    'customObject' => $customObject,
                    'availableFieldTypes' => $availableFieldTypes,
                    'customFields' => [],
                    'form'   => $form->createView(),
                ],
                'contentTemplate' => 'CustomObjectsBundle:CustomObject:form.html.php',
                'passthroughVars' => [
                    'mauticContent' => 'customObject',
                    'route'         => $this->routeProvider->buildNewRoute(),
                ],
            ]
        );
    }
}