<?php

declare(strict_types=1);

namespace MauticPlugin\CustomObjectsBundle\Controller\CustomField;

use Mautic\CoreBundle\Controller\CommonController;
use MauticPlugin\CustomObjectsBundle\Entity\CustomFieldFactory;
use MauticPlugin\CustomObjectsBundle\Entity\CustomObject;
use MauticPlugin\CustomObjectsBundle\Exception\ForbiddenException;
use MauticPlugin\CustomObjectsBundle\Exception\NotFoundException;
use MauticPlugin\CustomObjectsBundle\Form\Type\CustomFieldType;
use MauticPlugin\CustomObjectsBundle\Model\CustomFieldModel;
use MauticPlugin\CustomObjectsBundle\Model\CustomObjectModel;
use MauticPlugin\CustomObjectsBundle\Provider\CustomFieldPermissionProvider;
use MauticPlugin\CustomObjectsBundle\Provider\CustomFieldRouteProvider;
use MauticPlugin\CustomObjectsBundle\Provider\CustomObjectRouteProvider;
use Symfony\Component\Form\FormFactory;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class FormController extends CommonController
{
    /**
     * @var FormFactoryInterface
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

    public function __construct(
        FormFactoryInterface $formFactory,
        CustomFieldModel $customFieldModel,
        CustomFieldFactory $customFieldFactory,
        CustomFieldPermissionProvider $permissionProvider,
        CustomFieldRouteProvider $fieldRouteProvider,
        CustomObjectModel $customObjectModel,
        CustomObjectRouteProvider $objectRouteProvider
    ) {
        $this->formFactory             = $formFactory;
        $this->customFieldModel        = $customFieldModel;
        $this->customFieldFactory      = $customFieldFactory;
        $this->permissionProvider      = $permissionProvider;
        $this->fieldRouteProvider      = $fieldRouteProvider;
        $this->customObjectModel       = $customObjectModel;
        $this->objectRouteProvider     = $objectRouteProvider;
    }

    public function renderFormAction(Request $request): Response
    {
        $objectId   = (int) $request->get('objectId');
        $fieldId    = (int) $request->get('fieldId');
        $fieldType  = $request->get('fieldType');
        $panelId    = is_numeric($request->get('panelId')) ? (int) $request->get('panelId') : null; // Is edit of existing panel in view
        $panelCount = is_numeric($request->get('panelCount')) ? (int) $request->get('panelCount') : null;

        if ($objectId) {
            $customObject = $this->customObjectModel->fetchEntity($objectId);
        } else {
            $customObject = new CustomObject();
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
            return $this->accessDenied(false, $e->getMessage());
        }

        $route  = $this->fieldRouteProvider->buildFormRoute($customField->getId());
        $action = $this->fieldRouteProvider->buildSaveRoute($fieldType, $fieldId, $customObject->getId(), $panelCount, $panelId);
        $form   = $this->formFactory->create(CustomFieldType::class, $customField, ['action' => $action]);

        return $this->delegateView(
            [
                'returnUrl'      => $this->objectRouteProvider->buildEditRoute($customObject->getId()),
                'viewParameters' => [
                    'customObject' => $customObject,
                    'customField'  => $customField,
                    'form'         => $form->createView(),
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
