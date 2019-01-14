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

use MauticPlugin\CustomObjectsBundle\Form\Type\CustomObjectType;
use MauticPlugin\CustomObjectsBundle\Model\CustomFieldModel;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Form\FormFactory;
use Mautic\CoreBundle\Controller\CommonController;
use Symfony\Component\HttpFoundation\JsonResponse;
use MauticPlugin\CustomObjectsBundle\Model\CustomObjectModel;
use MauticPlugin\CustomObjectsBundle\Exception\NotFoundException;
use MauticPlugin\CustomObjectsBundle\Exception\ForbiddenException;
use MauticPlugin\CustomObjectsBundle\Provider\CustomObjectPermissionProvider;
use MauticPlugin\CustomObjectsBundle\Provider\CustomObjectRouteProvider;

class EditController extends CommonController
{
    /**
     * @var FormFactory
     */
    private $formFactory;

    /**
     * @var CustomObjectModel
     */
    private $customObjectModel;

    /**
     * @var CustomFieldModel
     */
    private $customFieldModel;

    /**
     * @var CustomObjectPermissionProvider
     */
    private $permissionProvider;

    /**
     * @var CustomObjectRouteProvider
     */
    private $routeProvider;

    /**
     * @param FormFactory                    $formFactory
     * @param CustomObjectModel              $customObjectModel
     * @param CustomFieldModel               $customFieldModel
     * @param CustomObjectPermissionProvider $permissionProvider
     * @param CustomObjectRouteProvider      $routeProvider
     */
    public function __construct(
        FormFactory $formFactory,
        CustomObjectModel $customObjectModel,
        CustomFieldModel $customFieldModel,
        CustomObjectPermissionProvider $permissionProvider,
        CustomObjectRouteProvider $routeProvider
    )
    {
        $this->formFactory        = $formFactory;
        $this->customObjectModel  = $customObjectModel;
        $this->customFieldModel = $customFieldModel;
        $this->permissionProvider = $permissionProvider;
        $this->routeProvider      = $routeProvider;
    }

    /**
     * @param int $objectId
     *
     * @return Response|JsonResponse
     */
    public function renderFormAction(int $objectId)
    {
        try {
            $entity = $this->customObjectModel->fetchEntity($objectId);
            $this->permissionProvider->canEdit($entity);
        } catch (NotFoundException $e) {
            return $this->notFound($e->getMessage());
        } catch (ForbiddenException $e) {
            $this->accessDenied(false, $e->getMessage());
        }

        $fields = $this->customFieldModel->fetchCustomFieldsForObject($entity);

        $action = $this->routeProvider->buildSaveRoute($objectId);
        $form   = $this->formFactory->create(CustomObjectType::class, $entity, ['action' => $action]);

        return $this->delegateView(
            [
                'returnUrl'      => $this->routeProvider->buildListRoute(),
                'viewParameters' => [
                    'entity' => $entity,
                    'availableFields' => $fields,
                    'formFields' => [],
                    'deletedFields' => [],
                    'form'   => $form->createView(),
                ],
                'contentTemplate' => 'CustomObjectsBundle:CustomObject:form.html.php',
                'passthroughVars' => [
//                    'activeLink'    => '#mautic_custom_object_list',
                    'mauticContent' => 'customObject',
                    'route'         => $this->routeProvider->buildEditRoute($objectId),
                ],
            ]
        );
    }
}