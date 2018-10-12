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
        if ($event->getRoute() === 'mautic_custom_object_structures_list') {
            // $event->addButton(
            //     [
            //         'attr' => [
            //             'class'       => 'btn btn-default btn-sm btn-nospin',
            //             'data-header' => $this->translator->trans('mautic.plugin.clearbit.button.caption'),
            //         ],
            //         'btnText'   => $this->translator->trans('mautic.plugin.clearbit.button.caption'),
            //         'iconClass' => 'fa fa-search',
            //     ],
            //     ButtonHelper::LOCATION_BULK_ACTIONS
            // );

            if ($event->getItem()) {
                $editButton = [
                    'attr' => [
                        'href' => $this->router->generate(
                            'mautic_custom_object_structures_new'// replace with edit,
                            // ['objectId' => $event->getItem()->getId(), 'objectAction' => 'lookupPerson']
                        ),
                    ],
                    'btnText'   => $this->translator->trans('mautic.core.form.edit'),
                    'iconClass' => 'fa fa-pencil-square-o',
                ];

                $event
                    // ->addButton(
                    //     $editButton,
                    //     ButtonHelper::LOCATION_PAGE_ACTIONS,
                    //     ['mautic_contact_action', ['objectAction' => 'view']]
                    // )
                    ->addButton(
                        $editButton,
                        ButtonHelper::LOCATION_LIST_ACTIONS,
                        'mautic_custom_object_structures_list'// replace with edit
                    );
            }
        }
    }
}
