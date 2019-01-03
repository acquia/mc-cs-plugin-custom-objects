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
use MauticPlugin\CustomObjectsBundle\Model\CustomObjectModel;

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
     * @var CustomObjectModel
     */
    private $customObjectModel;

    /**
     * @param FormFactory $formFactory
     * @param CustomItemPermissionProvider $permissionProvider
     * @param CustomItemRouteProvider $routeProvider
     * @param CustomObjectModel $customObjectModel
     */
    public function __construct(
        FormFactory $formFactory,
        CustomItemPermissionProvider $permissionProvider,
        CustomItemRouteProvider $routeProvider,
        CustomObjectModel $customObjectModel
    )
    {
        $this->formFactory        = $formFactory;
        $this->permissionProvider = $permissionProvider;
        $this->routeProvider      = $routeProvider;
        $this->customObjectModel  = $customObjectModel;
    }

    /**
     * @return Response|JsonResponse
     */
    public function renderFormAction(int $objectId)
    {
        try {
            $customObject = $this->customObjectModel->fetchEntity($objectId);
            $this->permissionProvider->canCreate();
        } catch (NotFoundException $e) {
            return $this->notFound($e->getMessage());
        } catch (ForbiddenException $e) {
            $this->accessDenied(false, $e->getMessage());
        }
        
        $entity  = new CustomItem($customObject);
        $action  = $this->routeProvider->buildSaveRoute($objectId);
        $form    = $this->formFactory->create(CustomItemType::class, $entity, ['action' => $action]);

        return $this->delegateView(
            [
                'returnUrl'      => $this->routeProvider->buildListRoute($objectId),
                'viewParameters' => [
                    'entity' => $entity,
                    'form'   => $form->createView(),
                ],
                'contentTemplate' => 'CustomObjectsBundle:CustomItem:form.html.php',
                'passthroughVars' => [
                    'mauticContent' => 'customItem',
                    'route'         => $this->routeProvider->buildNewRoute($objectId),
                ],
            ]
        );
    }
}