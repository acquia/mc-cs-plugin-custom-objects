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
use Mautic\CoreBundle\Controller\CommonController;
use Symfony\Component\HttpFoundation\JsonResponse;
use MauticPlugin\CustomObjectsBundle\Model\CustomItemModel;
use MauticPlugin\CustomObjectsBundle\Exception\NotFoundException;
use MauticPlugin\CustomObjectsBundle\Exception\ForbiddenException;
use MauticPlugin\CustomObjectsBundle\Provider\CustomItemPermissionProvider;
use MauticPlugin\CustomObjectsBundle\Provider\CustomItemRouteProvider;
use MauticPlugin\CustomObjectsBundle\Model\CustomObjectModel;

class FormController extends CommonController
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
     * @param FormFactory $formFactory
     * @param CustomObjectModel $customObjectModel
     * @param CustomItemModel $customItemModel
     * @param CustomItemPermissionProvider $permissionProvider
     * @param CustomItemRouteProvider $routeProvider
     */
    public function __construct(
        FormFactory $formFactory,
        CustomObjectModel $customObjectModel,
        CustomItemModel $customItemModel,
        CustomItemPermissionProvider $permissionProvider,
        CustomItemRouteProvider $routeProvider
    )
    {
        $this->formFactory        = $formFactory;
        $this->customObjectModel  = $customObjectModel;
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
    public function renderFormAction(int $objectId, ?int $itemId)
    {
        try {
            $customObject = $this->customObjectModel->fetchEntity($objectId);

            if ($itemId) {
                $entity = $this->customItemModel->fetchEntity($itemId);
                $route  = $this->routeProvider->buildEditRoute($objectId, $itemId);
                $this->permissionProvider->canEdit($entity);
            } else {
                $entity = $this->customItemModel->populateCustomFields(new CustomItem($customObject));
                $route  = $this->routeProvider->buildNewRoute($objectId);
            }
        } catch (NotFoundException $e) {
            return $this->notFound($e->getMessage());
        } catch (ForbiddenException $e) {
            $this->accessDenied(false, $e->getMessage());
        }

        $action = $this->routeProvider->buildSaveRoute($objectId, $itemId);
        $form   = $this->formFactory->create(CustomItemType::class, $entity, ['action' => $action, 'objectId' => $objectId]);

        return $this->delegateView(
            [
                'returnUrl'      => $this->routeProvider->buildListRoute($objectId),
                'viewParameters' => [
                    'entity'       => $entity,
                    'customObject' => $customObject,
                    'form'         => $form->createView(),
                ],
                'contentTemplate' => 'CustomObjectsBundle:CustomItem:form.html.php',
                'passthroughVars' => [
                    'mauticContent' => 'customItem',
                    'route'         => $route,
                ],
            ]
        );
    }
}