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

use MauticPlugin\CustomObjectsBundle\Entity\CustomItem;
use MauticPlugin\CustomObjectsBundle\Form\Type\CustomItemType;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Form\FormFactory;
use Symfony\Component\HttpFoundation\Request;
use Mautic\CoreBundle\Controller\CommonController;
use Symfony\Component\HttpFoundation\JsonResponse;
use MauticPlugin\CustomObjectsBundle\Provider\CustomItemPermissionProvider;
use MauticPlugin\CustomObjectsBundle\Exception\ForbiddenException;
use MauticPlugin\CustomObjectsBundle\Provider\CustomItemRouteProvider;

class NewController extends CommonController
{
    /**
     * @var FormFactory
     */
    private $formFactory;

    /**
     * @var CustomItemPermissionProvider
     */
    private $permissionProvider;

    /**
     * @var CustomItemRouteProvider
     */
    private $routeProvider;

    /**
     * @param FormFactory $formFactory
     * @param CustomItemPermissionProvider $permissionProvider
     * @param CustomItemRouteProvider $routeProvider
     */
    public function __construct(
        FormFactory $formFactory,
        CustomItemPermissionProvider $permissionProvider,
        CustomItemRouteProvider $routeProvider
    )
    {
        $this->formFactory        = $formFactory;
        $this->permissionProvider = $permissionProvider;
        $this->routeProvider      = $routeProvider;
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
        
        $entity  = new CustomItem();
        $action  = $this->routeProvider->buildSaveRoute();
        $form    = $this->formFactory->create(CustomItemType::class, $entity, ['action' => $action]);

        return $this->delegateView(
            [
                'returnUrl'      => $this->routeProvider->buildListRoute(),
                'viewParameters' => [
                    'entity' => $entity,
                    'form'   => $form->createView(),
                ],
                'contentTemplate' => 'CustomObjectsBundle:CustomItem:form.html.php',
                'passthroughVars' => [
                    'mauticContent' => 'customItem',
                    'route'         => $this->routeProvider->buildNewRoute(),
                ],
            ]
        );
    }
}