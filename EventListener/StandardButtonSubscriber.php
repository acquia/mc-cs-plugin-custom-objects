<?php

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
use MauticPlugin\CustomObjectsBundle\Entity\CustomField;
use MauticPlugin\CustomObjectsBundle\Exception\ForbiddenException;
use MauticPlugin\CustomObjectsBundle\Provider\CustomFieldPermissionProvider;
use MauticPlugin\CustomObjectsBundle\Provider\CustomFieldRouteProvider;

abstract class CustomFieldButtonSubscriber extends CommonSubscriber
{
    /**
     * @var CustomFieldPermissionProvider
     */
    private $permissionProvider;

    /**
     * @var CustomFieldRouteProvider
     */
    private $routeProvider;

    /**
     * @param CustomFieldPermissionProvider $permissionProvider
     * @param CustomFieldRouteProvider $routeProvide
     */
    public function __construct(
        CustomFieldPermissionProvider $permissionProvider,
        CustomFieldRouteProvider $routeProvider
    )
    {
        $this->permissionProvider = $permissionProvider;
        $this->routeProvider      = $routeProvider;
    }

    /**
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return [
            CoreEvents::VIEW_INJECT_CUSTOM_BUTTONS => ['injectViewButtons', 0],
        ];
    }

    /**
     * @param CustomButtonEvent $event
     */
    public function injectViewButtons(CustomButtonEvent $event)
    {
        switch ($event->getRoute()) {
            case CustomFieldRouteProvider::ROUTE_LIST:
                $this->addEntityButtons($event, ButtonHelper::LOCATION_LIST_ACTIONS);
                try {
                    $event->addButton($this->defineNewButton(), ButtonHelper::LOCATION_PAGE_ACTIONS, $event->getRoute());
                } catch (ForbiddenException $e) {}
                break;
            
            case CustomFieldRouteProvider::ROUTE_VIEW:
                $this->addEntityButtons($event, ButtonHelper::LOCATION_PAGE_ACTIONS);
                $event->addButton($this->defineCloseButton(), ButtonHelper::LOCATION_PAGE_ACTIONS, $event->getRoute());
                break;
        }
    }

    /**
     * @param CustomButtonEvent $event
     * @param string            $location
     */
    private function addEntityButtons(CustomButtonEvent $event, $location): void
    {
        $entity = $event->getItem();
        if ($entity && $entity instanceof CustomField) {
            try {
                $event->addButton($this->defineDeleteButton($entity), $location, $event->getRoute());
            } catch (ForbiddenException $e) {}

            try {
                $event->addButton($this->defineCloneButton($entity), $location, $event->getRoute());
            } catch (ForbiddenException $e) {}

            try {
                $event->addButton($this->defineEditButton($entity), $location, $event->getRoute());
            } catch (ForbiddenException $e) {}
        }
    }

    /**
     * @param CustomField $entity
     * 
     * @return array
     * 
     * @throws ForbiddenException
     */
    private function defineEditButton(CustomField $entity): array
    {
        $this->permissionProvider->canEdit($entity);

        return [
            'attr'      => ['href' => $this->routeProvider->buildFormRoute($entity->getId())],
            'btnText'   => 'mautic.core.form.edit',
            'iconClass' => 'fa fa-pencil-square-o',
            'priority'  => 500,
        ];
    }

    /**
     * @return array
     */
    private function defineCloseButton(): array
    {
        return [
            'attr' => [
                'href' => $this->routeProvider->buildListRoute(),
                'class' => 'btn btn-default',
            ],
            'class' => 'btn btn-default',
            'btnText'   => 'mautic.core.form.close',
            'iconClass' => 'fa fa-fw fa-remove',
            'priority'  => 400,
        ];
    }

    /**
     * @param CustomField $entity
     * 
     * @return array
     * 
     * @throws ForbiddenException
     */
    private function defineCloneButton(CustomField $entity): array
    {
        $this->permissionProvider->canClone($entity);

        return [
            'attr'      => ['href' => $this->routeProvider->buildCloneRoute($entity->getId())],
            'btnText'   => 'mautic.core.form.clone',
            'iconClass' => 'fa fa-copy',
            'priority'  => 300,
        ];
    }

    /**
     * @param CustomField $entity
     * 
     * @return array
     * 
     * @throws ForbiddenException
     */
    private function defineDeleteButton(CustomField $entity): array
    {
        $this->permissionProvider->canDelete($entity);        

        return [
            'attr'      => ['href' => $this->routeProvider->buildDeleteRoute($entity->getId())],
            'btnText'   => 'mautic.core.form.delete',
            'iconClass' => 'fa fa-fw fa-trash-o text-danger',
            'priority'  => 0,
        ];
    }

    /**
     * @return array
     * 
     * @throws ForbiddenException
     */
    private function defineNewButton(): array
    {
        $this->permissionProvider->canCreate();        

        return [
            'attr'      => ['href' => $this->routeProvider->buildNewRoute()],
            'btnText'   => $this->translator->trans('mautic.core.form.new'),
            'iconClass' => 'fa fa-plus',
            'priority'  => 500,
        ];
    }
}
