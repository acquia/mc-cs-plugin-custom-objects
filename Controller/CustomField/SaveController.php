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

use Doctrine\Common\Collections\ArrayCollection;
use MauticPlugin\CustomObjectsBundle\Entity\CustomFieldFactory;
use MauticPlugin\CustomObjectsBundle\Entity\CustomObject;
use MauticPlugin\CustomObjectsBundle\Form\Type\CustomObjectType;
use MauticPlugin\CustomObjectsBundle\Model\CustomObjectModel;
use MauticPlugin\CustomObjectsBundle\Entity\CustomField;
use MauticPlugin\CustomObjectsBundle\Form\Type\CustomFieldType;
use MauticPlugin\CustomObjectsBundle\Model\CustomFieldModel;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Form\FormFactory;
use Symfony\Component\Translation\TranslatorInterface;
use Symfony\Component\HttpFoundation\Request;
use Mautic\CoreBundle\Controller\CommonController;
use Symfony\Component\HttpFoundation\JsonResponse;
use MauticPlugin\CustomObjectsBundle\Exception\NotFoundException;
use MauticPlugin\CustomObjectsBundle\Provider\CustomFieldPermissionProvider;
use MauticPlugin\CustomObjectsBundle\Exception\ForbiddenException;
use MauticPlugin\CustomObjectsBundle\Provider\CustomFieldRouteProvider;

/**
 * This controller is not used for saving to database, it is used only to generate forms and data validation.
 * Persisting is handled in:.
 *
 * @see \MauticPlugin\CustomObjectsBundle\Controller\CustomObject\SaveController::saveAction
 */
class SaveController extends CommonController
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
     * @var CustomFieldFactory
     */
    private $customFieldFactory;

    /**
     * @var CustomFieldPermissionProvider
     */
    private $permissionProvider;

    /**
     * @var CustomFieldRouteProvider
     */
    private $fieldRouteProvider;

    /**
     * @var CustomObjectModel
     */
    private $customObjectModel;

    /**
     * @param FormFactory                   $formFactory
     * @param TranslatorInterface           $translator
     * @param CustomFieldModel              $customFieldModel
     * @param CustomFieldFactory            $customFieldFactory
     * @param CustomFieldPermissionProvider $permissionProvider
     * @param CustomFieldRouteProvider      $routeProvider
     * @param CustomObjectModel             $customObjectModel
     */
    public function __construct(
        FormFactory $formFactory,
        TranslatorInterface $translator,
        CustomFieldModel $customFieldModel,
        CustomFieldFactory $customFieldFactory,
        CustomFieldPermissionProvider $permissionProvider,
        CustomFieldRouteProvider $routeProvider,
        CustomObjectModel $customObjectModel
    ) {
        $this->formFactory             = $formFactory;
        $this->translator              = $translator;
        $this->customFieldModel        = $customFieldModel;
        $this->customFieldFactory      = $customFieldFactory;
        $this->permissionProvider      = $permissionProvider;
        $this->fieldRouteProvider      = $routeProvider;
        $this->customObjectModel       = $customObjectModel;
    }

    /**
     * Not save but validate Custom Field data and return proper response. Check class description.
     *
     * @param Request $request
     *
     * @return Response|JsonResponse
     */
    public function saveAction(Request $request)
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

        $action = $this->fieldRouteProvider->buildSaveRoute($fieldType, $fieldId, $customObject->getId(), $panelCount, $panelId);
        $form   = $this->formFactory->create(CustomFieldType::class, $customField, ['action' => $action]);

        $form->handleRequest($request);

        if ($form->isValid()) {
            // Render Custom Field form RAT for Custom Object form.
            return $this->buildSuccessForm($customObject, $form->getData(), $request);
        }

        $route = $fieldId ? $this->fieldRouteProvider->buildFormRoute($fieldId) : '';

        // Render Custom Field form for modal.
        return $this->delegateView(
            [
                'returnUrl'      => $route,
                'viewParameters' => [
                    'customField' => $customField,
                    'form'        => $form->createView(),
                ],
                'contentTemplate' => 'CustomObjectsBundle:CustomField:form.html.php',
                'passthroughVars' => [
                    'mauticContent' => 'customField',
                    'route'         => $route,
                    'fieldOrderNo'  => $request->get('fieldOrderNo'),
                ],
            ]
        );
    }

    /**
     * Build custom field form PART to be used in custom object form as panel and close modal via ajax response.
     *
     * @param CustomObject $customObject
     * @param CustomField  $customField
     * @param Request      $request
     *
     * @return JsonResponse
     */
    private function buildSuccessForm(CustomObject $customObject, CustomField $customField, Request $request): JsonResponse
    {
        $panelId = is_numeric($request->get('panelId')) ? (int) $request->get('panelId') : null; // Is edit of existing panel in view

        if (null === $panelId) {
            // New panel
            $customField->setOrder(0); // Append new panel to top
            $panelId        = (int) $request->get('panelCount');
            $isNew          = true;
        }
        $rawCustomField = $request->get('custom_field');
        $customField->setDefaultValue($rawCustomField['defaultValue']);

        foreach ($customField->getOptions() as $option) {
            // Custom field relationship is missing when creating new options
            $option->setCustomField($customField);
        }

        $this->customFieldModel->setAlias($customField);

        $customFields = new ArrayCollection([$customField]);
        $customObject->setCustomFields($customFields);

        $form = $this->formFactory->create(
            CustomObjectType::class,
            $customObject
        );

        $template = $this->render(
            'CustomObjectsBundle:CustomObject:_form-fields.html.php',
            [
                'form'          => $form->createView(),
                'panelId'       => $panelId, // Panel id to me replaced if edit
                'customField'   => $customField,
                'customFields'  => $customFields,
                'customObject'  => $customObject,
                'deletedFields' => [],
            ]
        );

        $templateContent = $template->getContent();
        // Replace order indexes witch free one to prevent duplicates in panel list
        $templateContent = str_replace(
            ['_0_', '[0]'],
            ['_'.$panelId.'_', '['.$panelId.']'],
            $templateContent
        );

        return new JsonResponse([
            'content'    => $templateContent,
            'isNew'      => isset($isNew),
            'panelId'    => $panelId,
            'type'       => $customField->getType(),
            'closeModal' => 1,
        ]);
    }
}
