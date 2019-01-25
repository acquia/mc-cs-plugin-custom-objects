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

use MauticPlugin\CustomObjectsBundle\Entity\CustomField;
use MauticPlugin\CustomObjectsBundle\Form\Type\CustomFieldType;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Form\FormFactory;
use Symfony\Component\HttpFoundation\Request;
use Mautic\CoreBundle\Controller\CommonController;
use Symfony\Component\HttpFoundation\JsonResponse;
use MauticPlugin\CustomObjectsBundle\Model\CustomFieldModel;
use MauticPlugin\CustomObjectsBundle\Exception\NotFoundException;
use MauticPlugin\CustomObjectsBundle\Exception\ForbiddenException;
use MauticPlugin\CustomObjectsBundle\Provider\CustomFieldPermissionProvider;
use MauticPlugin\CustomObjectsBundle\Provider\CustomFieldRouteProvider;

class EditController extends CommonController
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
    private $routeProvider;

    /**
     * @param FormFactory $formFactory
     * @param CustomFieldModel $customFieldModel
     * @param CustomFieldPermissionProvider $permissionProvider
     * @param CustomFieldRouteProvider $routeProvider
     */
    public function __construct(
        FormFactory $formFactory,
        CustomFieldModel $customFieldModel,
        CustomFieldPermissionProvider $permissionProvider,
        CustomFieldRouteProvider $routeProvider
    )
    {
        $this->formFactory        = $formFactory;
        $this->customFieldModel   = $customFieldModel;
        $this->permissionProvider = $permissionProvider;
        $this->routeProvider      = $routeProvider;
    }

    /**
     * @param int $fieldId
     *
     * @return Response|JsonResponse
     */
    public function renderFormAction(int $fieldId)
    {
        try {
            $customField = $this->customFieldModel->fetchEntity($fieldId);
            $this->permissionProvider->canEdit($customField);
        } catch (NotFoundException $e) {
            return $this->notFound($e->getMessage());
        } catch (ForbiddenException $e) {
            $this->accessDenied(false, $e->getMessage());
        }

        $action  = $this->routeProvider->buildSaveRoute($fieldId);
        $form    = $this->formFactory->create(CustomFieldType::class, $customField, ['action' => $action]);

        return $this->delegateView(
            [
                'returnUrl'      => $this->routeProvider->buildListRoute(),
                'viewParameters' => [
                    'customField' => $customField,
                    'form'   => $form->createView(),
                ],
                'contentTemplate' => 'CustomObjectsBundle:CustomField:form.html.php',
                'passthroughVars' => [
                    'mauticContent' => 'customField',
                    'route'         => $this->routeProvider->buildEditRoute($fieldId),
                ],
            ]
        );
    }
}