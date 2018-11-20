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
use MauticPlugin\CustomObjectsBundle\Entity\CustomObject;

class ButtonSubscriber extends CommonSubscriber
{
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
            case 'mautic_custom_object_list':
                $this->addEntityButtons($event, ButtonHelper::LOCATION_LIST_ACTIONS);
                break;
            
            case 'mautic_custom_object_view':
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
        if ($entity && $entity instanceof CustomObject) {
            $event->addButton($this->defineDeleteButton($entity), $location, $event->getRoute());
            $event->addButton($this->defineCloneButton($entity), $location, $event->getRoute());
            $event->addButton($this->defineEditButton($entity), $location, $event->getRoute());
        }
    }

    /**
     * @todo add permissions
     * 
     * @param CustomObject $entity
     * 
     * @return array
     */
    private function defineEditButton(CustomObject $entity): array
    {
        return [
            'attr' => [
                'href' => $this->router->generate('mautic_custom_object_edit', ['objectId' => $entity->getId()]),
            ],
            'btnText'   => $this->translator->trans('mautic.core.form.edit'),
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
                'href' => $this->router->generate('mautic_custom_object_list'),
                'class' => 'btn btn-default',
            ],
            'class' => 'btn btn-default',
            'btnText'   => $this->translator->trans('mautic.core.form.close'),
            'iconClass' => 'fa fa-fw fa-remove',
            'priority'  => 400,
        ];
    }

    /**
     * @todo add permissions
     * 
     * @param CustomObject $entity
     * 
     * @return array
     */
    private function defineCloneButton(CustomObject $entity): array
    {
        return [
            'attr' => [
                'href' => $this->router->generate('mautic_custom_object_clone', ['objectId' => $entity->getId()]),
            ],
            'btnText'   => $this->translator->trans('mautic.core.form.clone'),
            'iconClass' => 'fa fa-copy',
            'priority'  => 300,
        ];
    }

    /**
     * @todo add permissions
     * 
     * @param CustomObject $entity
     * 
     * @return array
     */
    private function defineDeleteButton(CustomObject $entity): array
    {
        return [
            'attr' => [
                'href' => $this->router->generate('mautic_custom_object_delete', ['objectId' => $entity->getId()]),
            ],
            'btnText'   => $this->translator->trans('mautic.core.form.delete'),
            'iconClass' => 'fa fa-fw fa-trash-o text-danger',
            'priority'  => 0,
        ];
    }
}
