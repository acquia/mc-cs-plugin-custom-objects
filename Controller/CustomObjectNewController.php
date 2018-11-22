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

use MauticPlugin\CustomObjectsBundle\Entity\CustomObject;
use MauticPlugin\CustomObjectsBundle\Form\Type\CustomObjectType;
use Symfony\Bundle\FrameworkBundle\Routing\Router;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Form\FormFactory;
use Symfony\Component\HttpFoundation\Request;
use Mautic\CoreBundle\Controller\CommonController;
use Symfony\Component\HttpFoundation\JsonResponse;
use MauticPlugin\CustomObjectsBundle\Provider\CustomObjectPermissionProvider;
use MauticPlugin\CustomObjectsBundle\Exception\ForbiddenException;

class CustomObjectNewController extends CommonController
{
    /**
     * @var Router
     */
    private $router;

    /**
     * @var FormFactory
     */
    private $formFactory;

    /**
     * @var CustomObjectPermissionProvider
     */
    private $permissionProvider;

    /**
     * @param Router $router
     * @param FormFactory $formFactory
     * @param CustomObjectPermissionProvider $permissionProvider
     */
    public function __construct(
        Router $router,
        FormFactory $formFactory,
        CustomObjectPermissionProvider $permissionProvider
    )
    {
        $this->router             = $router;
        $this->formFactory        = $formFactory;
        $this->permissionProvider = $permissionProvider;
    }

    /**
     * @return Response|JsonResponse
     */
    public function renderFormAction()
    {
        try {
            $this->permissionProvider->canCreate();
        } catch (ForbiddenException $e) {
            return $this->accessDenied($e->getMessage());
        }
        
        $entity  = new CustomObject();
        $action  = $this->router->generate('mautic_custom_object_save');
        $form    = $this->formFactory->create(CustomObjectType::class, $entity, ['action' => $action]);

        return $this->delegateView(
            [
                'returnUrl'      => $this->router->generate('mautic_custom_object_list'),
                'viewParameters' => [
                    'entity' => $entity,
                    'form'   => $form->createView(),
                ],
                'contentTemplate' => 'CustomObjectsBundle:CustomObject:form.html.php',
                'passthroughVars' => [
                    'mauticContent' => 'customObject',
                    'route'         => $this->router->generate('mautic_custom_object_new'),
                ],
            ]
        );
    }
}