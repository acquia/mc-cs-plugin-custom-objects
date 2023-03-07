<?php

declare(strict_types=1);

namespace MauticPlugin\CustomObjectsBundle\Controller\CustomField;

use Doctrine\Common\Collections\ArrayCollection;
use Mautic\CoreBundle\Controller\CommonController;
use MauticPlugin\CustomObjectsBundle\Entity\CustomField;
use MauticPlugin\CustomObjectsBundle\Entity\CustomFieldFactory;
use MauticPlugin\CustomObjectsBundle\Entity\CustomObject;
use MauticPlugin\CustomObjectsBundle\Exception\ForbiddenException;
use MauticPlugin\CustomObjectsBundle\Exception\NotFoundException;
use MauticPlugin\CustomObjectsBundle\Form\Type\CustomFieldType;
use MauticPlugin\CustomObjectsBundle\Form\Type\CustomObjectType;
use MauticPlugin\CustomObjectsBundle\Model\CustomFieldModel;
use MauticPlugin\CustomObjectsBundle\Model\CustomObjectModel;
use MauticPlugin\CustomObjectsBundle\Provider\CustomFieldPermissionProvider;
use MauticPlugin\CustomObjectsBundle\Provider\CustomFieldRouteProvider;
use Symfony\Component\Form\FormFactory;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\Translation\TranslatorInterface;

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

    public function __construct(
        FormFactory $formFactory,
        TranslatorInterface $translator,
        CustomFieldModel $customFieldModel,
        CustomFieldFactory $customFieldFactory,
        CustomFieldPermissionProvider $permissionProvider,
        CustomFieldRouteProvider $fieldRouteProvider,
        CustomObjectModel $customObjectModel
    ) {
        $this->formFactory             = $formFactory;
        $this->translator              = $translator;
        $this->customFieldModel        = $customFieldModel;
        $this->customFieldFactory      = $customFieldFactory;
        $this->permissionProvider      = $permissionProvider;
        $this->fieldRouteProvider      = $fieldRouteProvider;
        $this->customObjectModel       = $customObjectModel;
    }

    /**
     * Not save but validate Custom Field data and return proper response. Check class description.
     *
     * @return Response|JsonResponse
     */
    public function saveAction(Request $request)
    {
        $objectId   = (int) $request->get('objectId');
        $fieldId    = (int) $request->query->get('fieldId');
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

        $this->recreateOptionsFromPost($request->get('custom_field'), $customField);

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
                'contentTemplate' => 'CustomObjectsBundle:CustomField:form.html.twig',
                'passthroughVars' => [
                    'mauticContent' => 'customField',
                    'route'         => $route,
                    'fieldOrderNo'  => $request->get('fieldOrderNo'),
                ],
            ]
        );
    }

    /**
     * Here is CO form panel part build and injected in the frontend as part of existing CO form.
     */
    private function buildSuccessForm(CustomObject $customObject, CustomField $customField, Request $request): JsonResponse
    {
        $panelId = is_numeric($request->get('panelId')) ? (int) $request->get('panelId') : null; // Is edit of existing panel in view

        if (null === $panelId) {
            // New panel
            $customField->setOrder(0); // Append new panel to top
            $panelId        = (int) $request->get('panelCount');
            $isNew          = true;
            $rawCustomField = $request->get('custom_field');
            if (isset($rawCustomField['defaultValue'])) {
                $customField->setDefaultValue($rawCustomField['defaultValue']);
            }
        }

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
            'CustomObjectsBundle:CustomObject:_form-fields.html.twig',
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

    /**
     * Hack for Symfony form adding logic used in options which does not work as expected.
     * It was failing when new option was added and used as default with.
     *
     * @see \Symfony\Component\Form\Exception\TransformationFailedException
     *
     * @param string[] $customFieldPost
     */
    private function recreateOptionsFromPost(array $customFieldPost, CustomField $customField): void
    {
        if (empty($customFieldPost['options']['list'])) {
            return;
        }

        foreach ($customField->getOptions() as $option) {
            $customField->removeOption($option);
        }

        $optionsFromPost = $customFieldPost['options']['list'];

        foreach ($optionsFromPost as $optionFromPost) {
            $customField->addOption($optionFromPost);
        }
    }
}
