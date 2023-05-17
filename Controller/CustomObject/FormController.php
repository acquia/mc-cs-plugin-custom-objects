<?php

declare(strict_types=1);

namespace MauticPlugin\CustomObjectsBundle\Controller\CustomObject;

use Doctrine\Persistence\ManagerRegistry;
use Mautic\CoreBundle\Controller\AbstractFormController;
use Mautic\CoreBundle\Helper\UserHelper;
use Mautic\CoreBundle\Security\Permissions\CorePermissions;
use MauticPlugin\CustomObjectsBundle\Entity\CustomObject;
use MauticPlugin\CustomObjectsBundle\Exception\ForbiddenException;
use MauticPlugin\CustomObjectsBundle\Exception\NotFoundException;
use MauticPlugin\CustomObjectsBundle\Form\Type\CustomObjectType;
use MauticPlugin\CustomObjectsBundle\Helper\LockFlashMessageHelper;
use MauticPlugin\CustomObjectsBundle\Model\CustomFieldModel;
use MauticPlugin\CustomObjectsBundle\Model\CustomObjectModel;
use MauticPlugin\CustomObjectsBundle\Provider\CustomFieldTypeProvider;
use MauticPlugin\CustomObjectsBundle\Provider\CustomObjectPermissionProvider;
use MauticPlugin\CustomObjectsBundle\Provider\CustomObjectRouteProvider;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;

class FormController extends AbstractFormController
{
    public function __construct(
        CorePermissions $security,
        UserHelper $userHelper,
        ManagerRegistry $managerRegistry,
        RequestStack $requestStack
    ) {
        $this->setRequestStack($requestStack);

        parent::__construct($security, $userHelper, $managerRegistry);
    }

    public function newAction(
        CustomObjectPermissionProvider $permissionProvider,
        FormFactoryInterface $formFactory,
        CustomObjectRouteProvider $routeProvider,
        CustomFieldTypeProvider $customFieldTypeProvider,
        CustomFieldModel $customFieldModel
    ): Response {
        try {
            $permissionProvider->canCreate();
            $customObject = new CustomObject();
        } catch (ForbiddenException $e) {
            return $this->accessDenied(false, $e->getMessage());
        }

        return $this->renderCustomObjectForm(
            $formFactory,
            $routeProvider,
            $customFieldTypeProvider,
            $customFieldModel,
            $customObject,
            $routeProvider->buildNewRoute()
        );
    }

    public function editAction(
        CustomObjectModel $customObjectModel,
        CustomObjectPermissionProvider $permissionProvider,
        LockFlashMessageHelper $lockFlashMessageHelper,
        FormFactoryInterface $formFactory,
        CustomObjectRouteProvider $routeProvider,
        CustomFieldTypeProvider $customFieldTypeProvider,
        CustomFieldModel $customFieldModel,
        int $objectId
    ): Response {
        try {
            $customObject = $customObjectModel->fetchEntity($objectId);
            $permissionProvider->canEdit($customObject);
        } catch (NotFoundException $e) {
            return $this->notFound($e->getMessage());
        } catch (ForbiddenException $e) {
            return $this->accessDenied(false, $e->getMessage());
        }

        if ($customObjectModel->isLocked($customObject)) {
            $lockFlashMessageHelper->addFlash(
                $customObject,
                $routeProvider->buildEditRoute($objectId),
                $this->canEdit($customObject),
                'custom.object'
            );

            return $this->redirect($routeProvider->buildViewRoute($objectId));
        }

        $customObjectModel->lockEntity($customObject);

        return $this->renderCustomObjectForm(
            $formFactory,
            $routeProvider,
            $customFieldTypeProvider,
            $customFieldModel,
            $customObject,
            $routeProvider->buildEditRoute($objectId)
        );
    }

    public function cloneAction(
        CustomObjectModel $customObjectModel,
        CustomObjectPermissionProvider $permissionProvider,
        FormFactoryInterface $formFactory,
        CustomObjectRouteProvider $routeProvider,
        CustomFieldTypeProvider $customFieldTypeProvider,
        CustomFieldModel $customFieldModel,
        int $objectId
    ): Response {
        try {
            $customObject = clone $customObjectModel->fetchEntity($objectId);
            $permissionProvider->canClone($customObject);
        } catch (NotFoundException $e) {
            return $this->notFound($e->getMessage());
        } catch (ForbiddenException $e) {
            return $this->accessDenied(false, $e->getMessage());
        }

        return $this->renderCustomObjectForm(
            $formFactory,
            $routeProvider,
            $customFieldTypeProvider,
            $customFieldModel,
            $customObject,
            $routeProvider->buildCloneRoute($objectId)
        );
    }

    private function renderCustomObjectForm(
        FormFactoryInterface $formFactory,
        CustomObjectRouteProvider $routeProvider,
        CustomFieldTypeProvider $customFieldTypeProvider,
        CustomFieldModel $customFieldModel,
        CustomObject $customObject,
        string $route
    ): Response {
        $form = $formFactory->create(
            CustomObjectType::class,
            $customObject,
            ['action' => $routeProvider->buildSaveRoute($customObject->getId())]
        );

        return $this->delegateView(
            [
                'returnUrl'      => $routeProvider->buildListRoute(),
                'viewParameters' => [
                    'customObject'        => $customObject,
                    'availableFieldTypes' => $customFieldTypeProvider->getTypes(),
                    'customFields'        => $customFieldModel->fetchCustomFieldsForObject($customObject),
                    'deletedFields'       => [],
                    'form'                => $form->createView(),
                ],
                'contentTemplate' => '@CustomObjects/CustomObject/form.html.twig',
                'passthroughVars' => [
                    'mauticContent' => 'customObject',
                    'route'         => $route,
                ],
            ]
        );
    }
}
