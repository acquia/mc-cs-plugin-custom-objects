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

namespace MauticPlugin\CustomObjectsBundle\EventListener;

use Mautic\CoreBundle\CoreEvents;
use Mautic\CoreBundle\Event\CustomButtonEvent;
use Mautic\CoreBundle\EventListener\CommonSubscriber;
use Mautic\CoreBundle\Templating\Helper\ButtonHelper;
use MauticPlugin\CustomObjectsBundle\Entity\CustomObject;
use MauticPlugin\CustomObjectsBundle\Exception\ForbiddenException;
use MauticPlugin\CustomObjectsBundle\Provider\CustomItemPermissionProvider;
use MauticPlugin\CustomObjectsBundle\Provider\CustomItemRouteProvider;
use MauticPlugin\CustomObjectsBundle\Provider\CustomObjectPermissionProvider;
use MauticPlugin\CustomObjectsBundle\Provider\CustomObjectRouteProvider;

class CustomObjectButtonSubscriber extends CommonSubscriber
{
    /**
     * @var CustomObjectPermissionProvider
     */
    private $permissionProvider;

    /**
     * @var CustomObjectRouteProvider
     */
    private $routeProvider;

    /**
     * @var CustomItemPermissionProvider
     */
    private $customItemPermissionProvider;

    /**
     * @var CustomItemRouteProvider
     */
    private $customItemRouteProvider;

    /**
     * @param CustomObjectPermissionProvider $permissionProvider
     * @param CustomObjectRouteProvider      $routeProvider
     * @param CustomItemPermissionProvider   $customItemPermissionProvider
     * @param CustomItemRouteProvider        $customItemRouteProvider
     */
    public function __construct(
        CustomObjectPermissionProvider $permissionProvider,
        CustomObjectRouteProvider $routeProvider,
        CustomItemPermissionProvider $customItemPermissionProvider,
        CustomItemRouteProvider $customItemRouteProvider
    ) {
        $this->permissionProvider = $permissionProvider;
        $this->routeProvider      = $routeProvider;
        $this->customItemPermissionProvider = $customItemPermissionProvider;
        $this->customItemRouteProvider = $customItemRouteProvider;
    }

    /**
     * @return mixed[]
     */
    public static function getSubscribedEvents(): array
    {
        return [
            CoreEvents::VIEW_INJECT_CUSTOM_BUTTONS => ['injectViewButtons', 0],
        ];
    }

    /**
     * @param CustomButtonEvent $event
     */
    public function injectViewButtons(CustomButtonEvent $event): void
    {
        switch ($event->getRoute()) {
            case CustomObjectRouteProvider::ROUTE_LIST:
                $this->addEntityButtons($event, ButtonHelper::LOCATION_LIST_ACTIONS);

                try {
                    $event->addButton($this->defineNewButton(), ButtonHelper::LOCATION_PAGE_ACTIONS, $event->getRoute());
                } catch (ForbiddenException $e) {
                }

                break;

            case CustomObjectRouteProvider::ROUTE_VIEW:
                $this->addEntityButtons($event, ButtonHelper::LOCATION_PAGE_ACTIONS);
                $event->addButton($this->defineCloseButton(), ButtonHelper::LOCATION_PAGE_ACTIONS, $event->getRoute());

                if ($customObject = $event->getItem()) {
                    $event->addButton($this->defineViewCustomItemsButton($customObject), ButtonHelper::LOCATION_PAGE_ACTIONS, $event->getRoute());
                    $event->addButton($this->defineCreateNewCustomItemButton($customObject), ButtonHelper::LOCATION_PAGE_ACTIONS, $event->getRoute());
                }

                break;
        }
    }

    /**
     * @param CustomButtonEvent $event
     * @param string            $location
     */
    private function addEntityButtons(CustomButtonEvent $event, string $location): void
    {
        $entity = $event->getItem();
        if ($entity && $entity instanceof CustomObject) {
            try {
                $event->addButton($this->defineDeleteButton($entity), $location, $event->getRoute());
            } catch (ForbiddenException $e) {
            }

            try {
                $event->addButton($this->defineCloneButton($entity), $location, $event->getRoute());
            } catch (ForbiddenException $e) {
            }

            try {
                $event->addButton($this->defineEditButton($entity), $location, $event->getRoute());
            } catch (ForbiddenException $e) {
            }
        }
    }

    /**
     * @param CustomObject $entity
     *
     * @return mixed[]
     *
     * @throws ForbiddenException
     */
    private function defineEditButton(CustomObject $entity): array
    {
        $this->permissionProvider->canEdit($entity);

        return [
            'attr' => [
                'href' => $this->routeProvider->buildEditRoute($entity->getId()),
            ],
            'btnText'   => 'mautic.core.form.edit',
            'iconClass' => 'fa fa-pencil-square-o',
            'priority'  => 500,
        ];
    }

    /**
     * @return mixed[]
     */
    private function defineCloseButton(): array
    {
        return [
            'attr' => [
                'href'  => $this->routeProvider->buildListRoute(),
                'class' => 'btn btn-default',
            ],
            'class'     => 'btn btn-default',
            'btnText'   => 'mautic.core.form.close',
            'iconClass' => 'fa fa-fw fa-remove',
            'priority'  => 400,
        ];
    }

    /**
     * @param CustomObject $entity
     *
     * @return mixed[]
     *
     * @throws ForbiddenException
     */
    private function defineCloneButton(CustomObject $entity): array
    {
        $this->permissionProvider->canClone($entity);

        return [
            'attr' => [
                'href' => $this->routeProvider->buildCloneRoute($entity->getId()),
            ],
            'btnText'   => 'mautic.core.form.clone',
            'iconClass' => 'fa fa-copy',
            'priority'  => 300,
        ];
    }

    /**
     * @param CustomObject $entity
     *
     * @return mixed[]
     *
     * @throws ForbiddenException
     */
    private function defineDeleteButton(CustomObject $entity): array
    {
        $this->permissionProvider->canDelete($entity);

        return [
            'attr' => [
                'href' => $this->routeProvider->buildDeleteRoute($entity->getId()),
            ],
            'btnText'   => 'mautic.core.form.delete',
            'iconClass' => 'fa fa-fw fa-trash-o text-danger',
            'priority'  => 0,
        ];
    }

    /**
     * @return mixed[]
     *
     * @throws ForbiddenException
     */
    private function defineNewButton(): array
    {
//        $this->permissionProvider->canCreate();

        return [
            'attr' => [
                'href' => $this->routeProvider->buildNewRoute(),
            ],
            'btnText'   => $this->translator->trans('mautic.core.form.new'),
            'iconClass' => 'fa fa-plus',
            'priority'  => 500,
        ];
    }

    /**
     * @param CustomObject $customObject
     *
     * @return array
     */
    private function defineViewCustomItemsButton(CustomObject $customObject): array
    {
        $this->customItemPermissionProvider->canViewAtAll($customObject->getId());

        return [
            'attr' => [
                'href' => $this->customItemRouteProvider->buildListRoute($customObject->getId()),
            ],
            'btnText'   => 'custom.items.view.link',
            'iconClass' => 'fa fa-fw fa-list-alt',
            'priority'  => 0,
        ];
    }

    /**
     * @param CustomObject $customObject
     *
     * @return array
     */
    private function defineCreateNewCustomItemButton(CustomObject $customObject): array
    {
        $this->customItemPermissionProvider->canCreate($customObject->getId());

        return [
            'attr' => [
                'href' => $this->customItemRouteProvider->buildNewRoute($customObject->getId()),
            ],
            'btnText'   => 'custom.item.create.link',
            'iconClass' => 'fa fa-fw fa-plus',
            'priority'  => 0,
        ];
    }
}
