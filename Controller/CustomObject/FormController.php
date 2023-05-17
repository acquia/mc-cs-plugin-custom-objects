<?php

declare(strict_types=1);

namespace MauticPlugin\CustomObjectsBundle\Controller\CustomObject;

use Mautic\CoreBundle\Controller\AbstractFormController;
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
use Symfony\Component\Form\FormFactory;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;

class FormController extends AbstractFormController
{
    /**
     * @var FormFactoryInterface
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
     * @var CustomFieldTypeProvider
     */
    private $customFieldTypeProvider;

    /**
     * @var LockFlashMessageHelper
     */
    private $lockFlashMessageHelper;

    private RequestStack $requestStack;

    public function __construct(
        FormFactoryInterface $formFactory,
        CustomObjectModel $customObjectModel,
        CustomFieldModel $customFieldModel,
        CustomObjectPermissionProvider $permissionProvider,
        CustomObjectRouteProvider $routeProvider,
        CustomFieldTypeProvider $customFieldTypeProvider,
        LockFlashMessageHelper $lockFlashMessageHelper,
        RequestStack $requestStack
    ) {
        $this->formFactory             = $formFactory;
        $this->customObjectModel       = $customObjectModel;
        $this->customFieldModel        = $customFieldModel;
        $this->permissionProvider      = $permissionProvider;
        $this->routeProvider           = $routeProvider;
        $this->customFieldTypeProvider = $customFieldTypeProvider;
        $this->lockFlashMessageHelper  = $lockFlashMessageHelper;

        $this->requestStack           = $requestStack;
        parent::setRequestStack($requestStack);
    }

    public function newAction(Request $request): Response
    {
        try {
            $this->permissionProvider->canCreate();
            $customObject = new CustomObject();
        } catch (ForbiddenException $e) {
            return $this->accessDenied(false, $e->getMessage());
        }

        return $this->renderForm($customObject, $this->routeProvider->buildNewRoute());
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

        return $this->renderForm($customObject, $this->routeProvider->buildEditRoute($objectId));
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

        return $this->renderForm($customObject, $this->routeProvider->buildCloneRoute($objectId));
    }

    private function renderForm(CustomObject $customObject, string $route): Response
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
                'contentTemplate' => 'CustomObjectsBundle:CustomObject:form.html.php',
                'passthroughVars' => [
                    'mauticContent' => 'customObject',
                    'route'         => $route,
                ],
            ]
        );
    }
}
