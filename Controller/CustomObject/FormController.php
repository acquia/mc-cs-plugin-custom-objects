<?php

declare(strict_types=1);

namespace MauticPlugin\CustomObjectsBundle\Controller\CustomObject;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Form\FormFactoryInterface;
use Doctrine\Persistence\ManagerRegistry;
use Mautic\CoreBundle\Factory\MauticFactory;
use Mautic\CoreBundle\Factory\ModelFactory;
use Mautic\CoreBundle\Helper\UserHelper;
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\CoreBundle\Service\FlashBag;
use Mautic\CoreBundle\Translation\Translator;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Mautic\CoreBundle\Security\Permissions\CorePermissions;
use Mautic\CoreBundle\Controller\AbstractFormController;
use MauticPlugin\CustomObjectsBundle\Entity\CustomObject;
use MauticPlugin\CustomObjectsBundle\Model\CustomFieldModel;
use MauticPlugin\CustomObjectsBundle\Model\CustomObjectModel;
use MauticPlugin\CustomObjectsBundle\Form\Type\CustomObjectType;
use MauticPlugin\CustomObjectsBundle\Exception\NotFoundException;
use MauticPlugin\CustomObjectsBundle\Exception\ForbiddenException;
use MauticPlugin\CustomObjectsBundle\Helper\LockFlashMessageHelper;
use MauticPlugin\CustomObjectsBundle\Provider\CustomFieldTypeProvider;
use MauticPlugin\CustomObjectsBundle\Provider\CustomObjectRouteProvider;
use MauticPlugin\CustomObjectsBundle\Provider\CustomObjectPermissionProvider;

class FormController extends AbstractFormController
{
    public function __construct(
        private FormFactoryInterface $formFactory,
        ManagerRegistry $doctrine,
        MauticFactory $mauticFactory,
        ModelFactory $modelFactory,
        UserHelper $userHelper,
        CoreParametersHelper $coreParametersHelper,
        EventDispatcherInterface $dispatcher,
        Translator $translator,
        FlashBag $flashBag,
        RequestStack $requestStack,
        CorePermissions $security,
        private CustomObjectPermissionProvider $permissionProvider,
        private CustomObjectRouteProvider $routeProvider,
        private CustomObjectModel $customObjectModel,
        private CustomFieldModel $customFieldModel,
        private CustomFieldTypeProvider $customFieldTypeProvider,
        private LockFlashMessageHelper $lockFlashMessageHelper,
    ) {
        parent::__construct($doctrine, $mauticFactory, $modelFactory, $userHelper, $coreParametersHelper, $dispatcher, $translator, $flashBag, $requestStack, $security);
    }

    public function newAction(): Response
    {
        try {
            $this->permissionProvider->canCreate();
            $customObject = new CustomObject();
        } catch (ForbiddenException $e) {
            return $this->accessDenied(false, $e->getMessage());
        }

        return $this->renderObjectForm($customObject, $this->routeProvider->buildNewRoute());
    }

    public function editAction(int $objectId): Response
    {
        try {
            $customObject = $this->customObjectModel->fetchEntity($objectId);
            $this->permissionProvider->canEdit($customObject);
        } catch (NotFoundException $e) {
            return $this->notFound($e->getMessage());
        } catch (ForbiddenException $e) {
            return $this->accessDenied(false, $e->getMessage());
        }

        if ($this->customObjectModel->isLocked($customObject)) {
            $this->lockFlashMessageHelper->addFlash(
                $customObject,
                $this->routeProvider->buildEditRoute($objectId),
                $this->canEdit($customObject),
                'custom.object'
            );

            return $this->redirect($this->routeProvider->buildViewRoute($objectId));
        }

        $this->customObjectModel->lockEntity($customObject);

        return $this->renderObjectForm($customObject, $this->routeProvider->buildEditRoute($objectId));
    }

    public function cloneAction(int $objectId): Response
    {
        try {
            $customObject = clone $this->customObjectModel->fetchEntity($objectId);
            $this->permissionProvider->canClone($customObject);
        } catch (NotFoundException $e) {
            return $this->notFound($e->getMessage());
        } catch (ForbiddenException $e) {
            return $this->accessDenied(false, $e->getMessage());
        }

        return $this->renderObjectForm($customObject, $this->routeProvider->buildCloneRoute($objectId));
    }

    private function renderObjectForm(CustomObject $customObject, string $route): Response
    {
        $form = $this->formFactory->create(
            CustomObjectType::class,
            $customObject,
            ['action' => $this->routeProvider->buildSaveRoute($customObject->getId())]
        );

        return $this->delegateView(
            [
                'returnUrl'      => $this->routeProvider->buildListRoute(),
                'viewParameters' => [
                    'customObject'        => $customObject,
                    'availableFieldTypes' => $this->customFieldTypeProvider->getTypes(),
                    'customFields'        => $this->customFieldModel->fetchCustomFieldsForObject($customObject),
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
