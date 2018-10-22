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
        $entity = $event->getItem();
        // if ('mautic_custom_object_structures_list' === $event->getRoute() && 'list_action' === $event->getLocation()) {
        if ($entity && $entity instanceof CustomObjectStructure) {
            $editButton = [
                'attr' => [
                    'href' => $this->router->generate('mautic_custom_object_structures_edit', ['objectId' => $event->getItem()->getId()]),
                ],
                'btnText'   => $this->translator->trans('mautic.core.form.edit'),
                'iconClass' => 'fa fa-pencil-square-o',
            ];

            $event->addButton(
                $editButton,
                ButtonHelper::LOCATION_LIST_ACTIONS,
                'mautic_custom_object_structures_list'
            );
        }
    }
}
