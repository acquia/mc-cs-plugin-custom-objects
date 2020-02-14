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

namespace MauticPlugin\CustomObjectsBundle\Controller\CustomItem;

use Mautic\CoreBundle\Controller\AbstractFormController;
use MauticPlugin\CustomObjectsBundle\Entity\CustomItem;
use MauticPlugin\CustomObjectsBundle\Entity\CustomObject;
use MauticPlugin\CustomObjectsBundle\Exception\ForbiddenException;
use MauticPlugin\CustomObjectsBundle\Exception\NotFoundException;
use MauticPlugin\CustomObjectsBundle\Form\Type\CustomItemType;
use MauticPlugin\CustomObjectsBundle\Helper\LockFlashMessageHelper;
use MauticPlugin\CustomObjectsBundle\Model\CustomItemModel;
use MauticPlugin\CustomObjectsBundle\Model\CustomObjectModel;
use MauticPlugin\CustomObjectsBundle\Provider\CustomItemPermissionProvider;
use MauticPlugin\CustomObjectsBundle\Provider\CustomItemRouteProvider;
use Symfony\Component\Form\FormFactory;
use Symfony\Component\HttpFoundation\Response;

class FormController extends AbstractFormController
{
    /**
     * @var FormFactory
     */
    private $formFactory;

    /**
     * @var CustomItemModel
     */
    private $customItemModel;

    /**
     * @var CustomObjectModel
     */
    private $customObjectModel;

    /**
     * @var CustomItemPermissionProvider
     */
    private $permissionProvider;

    /**
     * @var CustomItemRouteProvider
     */
    private $routeProvider;

    /**
     * @var LockFlashMessageHelper
     */
    private $lockFlashMessageHelper;

    public function __construct(
        FormFactory $formFactory,
        CustomObjectModel $customObjectModel,
        CustomItemModel $customItemModel,
        CustomItemPermissionProvider $permissionProvider,
        CustomItemRouteProvider $routeProvider,
        LockFlashMessageHelper $lockFlashMessageHelper
    ) {
        $this->formFactory            = $formFactory;
        $this->customObjectModel      = $customObjectModel;
        $this->customItemModel        = $customItemModel;
        $this->permissionProvider     = $permissionProvider;
        $this->routeProvider          = $routeProvider;
        $this->lockFlashMessageHelper = $lockFlashMessageHelper;
    }

    public function newAction(int $objectId): Response
    {
        try {
            $this->permissionProvider->canCreate($objectId);
            $customObject = $this->customObjectModel->fetchEntity($objectId);
            $customItem   = $this->customItemModel->populateCustomFields(new CustomItem($customObject));
        } catch (ForbiddenException $e) {
            return $this->accessDenied(false, $e->getMessage());
        }

        return $this->renderFormForItem($customItem, $customObject, $this->routeProvider->buildNewRoute($objectId));
    }

    public function editAction(int $objectId, int $itemId): Response
    {
        try {
            $customObject = $this->customObjectModel->fetchEntity($objectId);
            $customItem   = $this->customItemModel->fetchEntity($itemId);
            $this->permissionProvider->canEdit($customItem);
        } catch (NotFoundException $e) {
            return $this->notFound($e->getMessage());
        } catch (ForbiddenException $e) {
            return $this->accessDenied(false, $e->getMessage());
        }

        if ($this->customItemModel->isLocked($customItem)) {
            $this->lockFlashMessageHelper->addFlash(
                $customItem,
                $this->routeProvider->buildEditRoute($objectId, $itemId),
                $this->canEdit($customItem),
                'custom.item'
            );

            return $this->redirect($this->routeProvider->buildViewRoute($objectId, $itemId));
        }

        $this->customItemModel->lockEntity($customItem);

        return $this->renderFormForItem($customItem, $customObject, $this->routeProvider->buildEditRoute($objectId, $itemId));
    }

    public function cloneAction(int $objectId, int $itemId): Response
    {
        try {
            $customItem = clone $this->customItemModel->fetchEntity($itemId);
            $this->permissionProvider->canClone($customItem);
        } catch (NotFoundException $e) {
            return $this->notFound($e->getMessage());
        } catch (ForbiddenException $e) {
            return $this->accessDenied(false, $e->getMessage());
        }

        $customItem->setName($customItem->getName().' '.$this->translator->trans('mautic.core.form.clone'));

        return $this->renderFormForItem($customItem, $customItem->getCustomObject(), $this->routeProvider->buildCloneRoute($objectId, $itemId));
    }

    private function renderFormForItem(CustomItem $customItem, CustomObject $customObject, string $route): Response
    {
        $action = $this->routeProvider->buildSaveRoute($customObject->getId(), $customItem->getId());
        $form   = $this->formFactory->create(
            CustomItemType::class,
            $customItem,
            ['action' => $action, 'objectId' => $customObject->getId()]
        );

        return $this->delegateView(
            [
                'returnUrl'      => $this->routeProvider->buildListRoute($customObject->getId()),
                'viewParameters' => [
                    'entity'       => $customItem,
                    'customObject' => $customObject,
                    'form'         => $form->createView(),
                ],
                'contentTemplate' => 'CustomObjectsBundle:CustomItem:form.html.php',
                'passthroughVars' => [
                    'mauticContent' => 'customItem',
                    'route'         => $route,
                ],
            ]
        );
    }
}
