<?php

declare(strict_types=1);

namespace MauticPlugin\CustomObjectsBundle\Controller\CustomField;

use Mautic\CoreBundle\Service\FlashBag;
use Mautic\CoreBundle\Helper\UserHelper;
use Doctrine\Persistence\ManagerRegistry;
use Mautic\CoreBundle\Factory\ModelFactory;
use Mautic\CoreBundle\Translation\Translator;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Form\FormFactoryInterface;
use Mautic\CoreBundle\Controller\CommonController;
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Symfony\Component\HttpFoundation\RequestStack;
use MauticPlugin\CustomObjectsBundle\Entity\CustomObject;
use Mautic\CoreBundle\Security\Permissions\CorePermissions;
use MauticPlugin\CustomObjectsBundle\Model\CustomFieldModel;
use MauticPlugin\CustomObjectsBundle\Model\CustomObjectModel;
use MauticPlugin\CustomObjectsBundle\Entity\CustomFieldFactory;
use MauticPlugin\CustomObjectsBundle\Form\Type\CustomFieldType;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use MauticPlugin\CustomObjectsBundle\Exception\NotFoundException;
use MauticPlugin\CustomObjectsBundle\Exception\ForbiddenException;
use MauticPlugin\CustomObjectsBundle\Provider\CustomFieldRouteProvider;
use MauticPlugin\CustomObjectsBundle\Provider\CustomObjectRouteProvider;
use MauticPlugin\CustomObjectsBundle\Provider\CustomFieldPermissionProvider;

class FormController extends CommonController
{
    public function __construct(
        private FormFactoryInterface $formFactory,
        ManagerRegistry $doctrine,
        ModelFactory $modelFactory,
        UserHelper $userHelper,
        CoreParametersHelper $coreParametersHelper,
        EventDispatcherInterface $dispatcher,
        Translator $translator,
        FlashBag $flashBag,
        RequestStack $requestStack,
        CorePermissions $security,
        private CustomObjectRouteProvider $objectRouteProvider,
        private CustomFieldRouteProvider $fieldRouteProvider,
        private CustomFieldFactory $customFieldFactory,
        private CustomObjectModel $customObjectModel,
        private CustomFieldModel $customFieldModel,
        private CustomFieldPermissionProvider $permissionProvider,
    ) {
        parent::__construct($doctrine, $modelFactory, $userHelper, $coreParametersHelper, $dispatcher, $translator, $flashBag, $requestStack, $security);
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
                'contentTemplate' => '@CustomObjects/CustomField/form.html.twig',
                'passthroughVars' => [
                    'mauticContent' => 'customField',
                    'route'         => $route,
                ],
            ]
        );
    }
}
