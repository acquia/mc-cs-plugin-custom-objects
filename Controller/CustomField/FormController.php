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
use MauticPlugin\CustomObjectsBundle\Form\Type\CustomFieldType;
use MauticPlugin\CustomObjectsBundle\Model\CustomObjectModel;
use MauticPlugin\CustomObjectsBundle\Provider\CustomObjectRouteProvider;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Form\FormFactory;
use Mautic\CoreBundle\Controller\CommonController;
use Symfony\Component\HttpFoundation\JsonResponse;
use MauticPlugin\CustomObjectsBundle\Model\CustomFieldModel;
use MauticPlugin\CustomObjectsBundle\Exception\NotFoundException;
use MauticPlugin\CustomObjectsBundle\Exception\ForbiddenException;
use MauticPlugin\CustomObjectsBundle\Provider\CustomFieldPermissionProvider;
use MauticPlugin\CustomObjectsBundle\Provider\CustomFieldRouteProvider;

class FormController extends CommonController
{
    /**
     * @var FormFactory
     */
    private $formFactory;

    /**
     * @var CustomFieldModel
     */
    private $customFieldModel;

    /**
     * @var CustomFieldPermissionProvider
     */
    private $permissionProvider;

    /**
     * @var CustomFieldRouteProvider
     */
    private $fieldRouteProvider;

    /**
     * @var CustomFieldFactory
     */
    private $customFieldFactory;

    /**
     * @var CustomObjectModel
     */
    private $customObjectModel;

    /**
     * @var CustomObjectRouteProvider
     */
    private $objectRouteProvider;

    /**
     * @param FormFactory                   $formFactory
     * @param CustomFieldModel              $customFieldModel
     * @param CustomFieldFactory            $customFieldFactory
     * @param CustomFieldPermissionProvider $permissionProvider
     * @param CustomFieldRouteProvider      $fieldRouteProvider
     * @param CustomObjectModel             $customObjectModel
     * @param CustomObjectRouteProvider     $objectRouteProvider
     */
    public function __construct(
        FormFactory $formFactory,
        CustomFieldModel $customFieldModel,
        CustomFieldFactory $customFieldFactory,
        CustomFieldPermissionProvider $permissionProvider,
        CustomFieldRouteProvider $fieldRouteProvider,
        CustomObjectModel $customObjectModel,
        CustomObjectRouteProvider $objectRouteProvider
    ){
        $this->formFactory        = $formFactory;
        $this->customFieldModel   = $customFieldModel;
        $this->customFieldFactory = $customFieldFactory;
        $this->permissionProvider = $permissionProvider;
        $this->fieldRouteProvider      = $fieldRouteProvider;
        $this->customObjectModel = $customObjectModel;
        $this->objectRouteProvider = $objectRouteProvider;
    }

    /**
     * @param Request $request
     *
     * @return Response|JsonResponse
     * @throws ForbiddenException
     */
    public function renderFormAction(Request $request)
    {
        $objectId = (int) $request->get('objectId');
        $fieldId = (int) $request->get('fieldId');
        $fieldType = $request->get('fieldType');

        try {
            $customObject = $this->customObjectModel->fetchEntity($objectId);
        } catch (NotFoundException $e) {
            return $this->notFound($e->getMessage());
        }

        try {
            if ($fieldId) {
                $customField = $this->customFieldModel->fetchEntity($fieldId);
                $this->permissionProvider->canEdit($customField);
            } else {
                $this->permissionProvider->canCreate();
                $customField = $this->customFieldFactory->create($fieldType, $customObject);
            }
        } catch (NotFoundException $e) {
            return $this->notFound($e->getMessage());
        } catch (ForbiddenException $e) {
            $this->accessDenied(false, $e->getMessage());
        }

        $route = $this->fieldRouteProvider->buildFormRoute($customObject->getId());
        $action = $this->fieldRouteProvider->buildSaveRoute($fieldId, $customObject->getId(), $fieldType);
        $form   = $this->formFactory->create(CustomFieldType::class, $customField, ['action' => $action]);

        return $this->delegateView(
            [
                'returnUrl'      => $this->objectRouteProvider->buildEditRoute($customObject->getId()),
                'viewParameters' => [
                    'customObject' => $customObject,
                    'customField' => $customField,
                    'form'   => $form->createView(),
                ],
                'contentTemplate' => 'CustomObjectsBundle:CustomField:form.html.php',
                'passthroughVars' => [
                    'mauticContent' => 'customField',
                    'route'         => $route,
                ],
            ]
        );
    }
}