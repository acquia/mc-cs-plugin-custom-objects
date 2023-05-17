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
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;

class FormController extends CommonController
{
    public function renderFormAction(
        RequestStack $requestStack,
        FormFactoryInterface $formFactory,
        CustomFieldModel $customFieldModel,
        CustomFieldFactory $customFieldFactory,
        CustomFieldPermissionProvider $permissionProvider,
        CustomFieldRouteProvider $fieldRouteProvider,
        CustomObjectModel $customObjectModel,
        CustomObjectRouteProvider $objectRouteProvider
    ): Response {
        $this->setRequestStack($requestStack);

        $request    = $requestStack->getCurrentRequest();
        $objectId   = (int) $request->get('objectId');
        $fieldId    = (int) $request->get('fieldId');
        $fieldType  = $request->get('fieldType');
        $panelId    = is_numeric($request->get('panelId')) ? (int) $request->get('panelId') : null; // Is edit of existing panel in view
        $panelCount = is_numeric($request->get('panelCount')) ? (int) $request->get('panelCount') : null;

        if ($objectId) {
            $customObject = $customObjectModel->fetchEntity($objectId);
        } else {
            $customObject = new CustomObject();
        }

        try {
            if ($fieldId) {
                $customField = $customFieldModel->fetchEntity($fieldId);
                $permissionProvider->canEdit($customField);
            } else {
                $permissionProvider->canCreate();
                $customField = $customFieldFactory->create($fieldType, $customObject);
            }
        } catch (NotFoundException $e) {
            return $this->notFound($e->getMessage());
        } catch (ForbiddenException $e) {
            return $this->accessDenied(false, $e->getMessage());
        }

        $route  = $fieldRouteProvider->buildFormRoute($customField->getId());
        $action = $fieldRouteProvider->buildSaveRoute($fieldType, $fieldId, $customObject->getId(), $panelCount, $panelId);
        $form   = $formFactory->create(CustomFieldType::class, $customField, ['action' => $action]);

        return $this->delegateView(
            [
                'returnUrl'      => $objectRouteProvider->buildEditRoute($customObject->getId()),
                'viewParameters' => [
                    'customObject' => $customObject,
                    'customField'  => $customField,
                    'form'         => $form->createView(),
                ],
                'contentTemplate' => '@CustomObjects/CustomField/form.html.twig',
                'passthroughVars' => [
                    'mauticContent' => 'customField',
                    'route'         => $route,
                ],
            ]
        );
    }
}
