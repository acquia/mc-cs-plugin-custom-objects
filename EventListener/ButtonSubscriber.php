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
use MauticPlugin\CustomObjectsBundle\Entity\CustomObjectStructure;

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
            case 'mautic_custom_object_structures_list':
                $this->addEntityButtons($event, ButtonHelper::LOCATION_LIST_ACTIONS);
                break;
            
            case 'mautic_custom_object_structures_view':
                $this->addEntityButtons($event, ButtonHelper::LOCATION_PAGE_ACTIONS);
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
        if ($entity && $entity instanceof CustomObjectStructure) {
            $event->addButton($this->defineDeleteButton($entity), $location, $event->getRoute());
            $event->addButton($this->defineCloneButton($entity), $location, $event->getRoute());
            $event->addButton($this->defineEditButton($entity), $location, $event->getRoute());
        }
    }

    /**
     * @todo add permissions
     * 
     * @param CustomObjectStructure $entity
     * 
     * @return array
     */
    private function defineEditButton(CustomObjectStructure $entity): array
    {
        return [
            'attr' => [
                'href' => $this->router->generate('mautic_custom_object_structures_edit', ['objectId' => $entity->getId()]),
            ],
            'btnText'   => $this->translator->trans('mautic.core.form.edit'),
            'iconClass' => 'fa fa-pencil-square-o',
            'priority'  => 500,
        ];
    }

    /**
     * @todo add permissions
     * 
     * @param CustomObjectStructure $entity
     * 
     * @return array
     */
    private function defineCloneButton(CustomObjectStructure $entity): array
    {
        return [
            'attr' => [
                'href' => $this->router->generate('mautic_custom_object_structures_clone', ['objectId' => $entity->getId()]),
            ],
            'btnText'   => $this->translator->trans('mautic.core.form.clone'),
            'iconClass' => 'fa fa-copy',
            'priority'  => 300,
        ];
    }

    /**
     * @todo add permissions
     * 
     * @param CustomObjectStructure $entity
     * 
     * @return array
     */
    private function defineDeleteButton(CustomObjectStructure $entity): array
    {
        return [
            'attr' => [
                'href' => $this->router->generate('mautic_custom_object_structures_edit', ['objectId' => $entity->getId()]),
            ],
            'btnText'   => $this->translator->trans('mautic.core.form.delete'),
            'iconClass' => 'fa fa-fw fa-trash-o text-danger',
            'priority'  => 0,
        ];
    }

}
