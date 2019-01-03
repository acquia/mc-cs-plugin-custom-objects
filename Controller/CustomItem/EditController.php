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
use MauticPlugin\CustomObjectsBundle\Model\CustomItemModel;
use MauticPlugin\CustomObjectsBundle\Exception\NotFoundException;
use MauticPlugin\CustomObjectsBundle\Exception\ForbiddenException;
use MauticPlugin\CustomObjectsBundle\Provider\CustomItemPermissionProvider;
use MauticPlugin\CustomObjectsBundle\Provider\CustomItemRouteProvider;

class EditController extends CommonController
{
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
     * @param FormFactory $formFactory
     * @param CustomItemModel $customItemModel
     * @param CustomItemPermissionProvider $permissionProvider
     * @param CustomItemRouteProvider $routeProvider
     */
    public function __construct(
        FormFactory $formFactory,
        CustomItemModel $customItemModel,
        CustomItemPermissionProvider $permissionProvider,
        CustomItemRouteProvider $routeProvider
    )
    {
        $this->formFactory        = $formFactory;
        $this->customItemModel    = $customItemModel;
        $this->permissionProvider = $permissionProvider;
        $this->routeProvider      = $routeProvider;
    }

    /**
     * @param int $objectId
     * @param int $itemId
     * 
     * @return Response|JsonResponse
     */
    public function renderFormAction(int $objectId, int $itemId)
    {
        try {
            $entity = $this->customItemModel->fetchEntity($objectId);
            $this->permissionProvider->canEdit($entity);
        } catch (NotFoundException $e) {
            return $this->notFound($e->getMessage());
        } catch (ForbiddenException $e) {
            $this->accessDenied(false, $e->getMessage());
        }

        $action = $this->routeProvider->buildSaveRoute($objectId, $itemId);
        $form   = $this->formFactory->create(CustomItemType::class, $entity, ['action' => $action]);

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
                    'route'         => $this->routeProvider->buildEditRoute($objectId, $itemId),
                ],
            ]
        );
    }
}