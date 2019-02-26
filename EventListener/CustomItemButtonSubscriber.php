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
use MauticPlugin\CustomObjectsBundle\Entity\CustomItem;
use MauticPlugin\CustomObjectsBundle\Exception\ForbiddenException;
use MauticPlugin\CustomObjectsBundle\Provider\CustomItemPermissionProvider;
use MauticPlugin\CustomObjectsBundle\Provider\CustomItemRouteProvider;
use Symfony\Component\Translation\TranslatorInterface;

class CustomItemButtonSubscriber extends CommonSubscriber
{
    /**
     * @var TranslatorInterface
     */
    protected $translator;

    /**
     * @var CustomItemPermissionProvider
     */
    private $permissionProvider;

    /**
     * @var CustomItemRouteProvider
     */
    private $routeProvider;

    /**
     * @param CustomItemPermissionProvider $permissionProvider
     * @param CustomItemRouteProvider      $routeProvider
     * @param TranslatorInterface          $translator
     */
    public function __construct(
        CustomItemPermissionProvider $permissionProvider,
        CustomItemRouteProvider $routeProvider,
        TranslatorInterface $translator
    ) {
        $this->permissionProvider = $permissionProvider;
        $this->routeProvider      = $routeProvider;
        $this->translator         = $translator;
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
            case CustomItemRouteProvider::ROUTE_LIST:
                try {
                    $customObjectId      = $this->getCustomObjectIdFromEvent($event);
                    $contactId           = $event->getRequest()->query->get('contactId', false);
                    $onContactDetailPage = (bool) $contactId;
                    if ($onContactDetailPage) {
                        $customItem = $event->getItem();
                        if ($customItem && $customItem instanceof CustomItem) {
                            $event->addButton(
                                $this->defineUnlinkContactButton($customObjectId, $customItem->getId(), (int) $contactId),
                                ButtonHelper::LOCATION_LIST_ACTIONS,
                                $event->getRoute()
                            );
                        }
                    } else {
                        $this->addEntityButtons($event, ButtonHelper::LOCATION_LIST_ACTIONS);
                        $event->addButton(
                            $this->defineNewButton($customObjectId),
                            ButtonHelper::LOCATION_PAGE_ACTIONS,
                            $event->getRoute()
                        );
                        $event->addButton(
                            $this->defineImportNewButton($customObjectId),
                            ButtonHelper::LOCATION_PAGE_ACTIONS,
                            $event->getRoute()
                        );
                        $event->addButton(
                            $this->defineImportListButton($customObjectId),
                            ButtonHelper::LOCATION_PAGE_ACTIONS,
                            $event->getRoute()
                        );
                        $event->addButton(
                            $this->defineBatchDeleteButton($customObjectId),
                            ButtonHelper::LOCATION_BULK_ACTIONS,
                            $event->getRoute()
                        );
                    }
                } catch (ForbiddenException $e) {
                }

                break;

            case CustomItemRouteProvider::ROUTE_VIEW:
                $this->addEntityButtons($event, ButtonHelper::LOCATION_PAGE_ACTIONS);
                $event->addButton(
                    $this->defineCloseButton($this->getCustomObjectIdFromEvent($event)),
                    ButtonHelper::LOCATION_PAGE_ACTIONS,
                    $event->getRoute()
                );

                break;
        }
    }

    /**
     * @param CustomButtonEvent $event
     * @param string            $location
     */
    private function addEntityButtons(CustomButtonEvent $event, string $location): void
    {
        $customItem = $event->getItem();
        if ($customItem && $customItem instanceof CustomItem) {
            $customObjectId = $this->getCustomObjectIdFromEvent($event);

            try {
                $event->addButton($this->defineDeleteButton($customObjectId, $customItem), $location, $event->getRoute());
            } catch (ForbiddenException $e) {
            }

            try {
                $event->addButton($this->defineCloneButton($customObjectId, $customItem), $location, $event->getRoute());
            } catch (ForbiddenException $e) {
            }

            try {
                $event->addButton($this->defineEditButton($customObjectId, $customItem), $location, $event->getRoute());
            } catch (ForbiddenException $e) {
            }
        }
    }

    /**
     * @param int        $customObjectId
     * @param CustomItem $customItem
     *
     * @return mixed[]
     *
     * @throws ForbiddenException
     */
    private function defineEditButton(int $customObjectId, CustomItem $customItem): array
    {
        $this->permissionProvider->canEdit($customItem);

        return [
            'attr' => [
                'href' => $this->routeProvider->buildEditRoute($customObjectId, $customItem->getId()),
            ],
            'btnText'   => 'mautic.core.form.edit',
            'iconClass' => 'fa fa-pencil-square-o',
            'priority'  => 500,
        ];
    }

    /**
     * @param int $customObjectId
     *
     * @return mixed[]
     */
    private function defineCloseButton(int $customObjectId): array
    {
        return [
            'attr' => [
                'href'  => $this->routeProvider->buildListRoute($customObjectId),
                'class' => 'btn btn-default',
            ],
            'class'     => 'btn btn-default',
            'btnText'   => 'mautic.core.form.close',
            'iconClass' => 'fa fa-fw fa-remove',
            'priority'  => 400,
        ];
    }

    /**
     * @param int        $customObjectId
     * @param CustomItem $customItem
     *
     * @return mixed[]
     *
     * @throws ForbiddenException
     */
    private function defineCloneButton(int $customObjectId, CustomItem $customItem): array
    {
        $this->permissionProvider->canClone($customItem);

        return [
            'attr' => [
                'href' => $this->routeProvider->buildCloneRoute($customObjectId, $customItem->getId()),
            ],
            'btnText'   => 'mautic.core.form.clone',
            'iconClass' => 'fa fa-copy',
            'priority'  => 300,
        ];
    }

    /**
     * @param int        $customObjectId
     * @param CustomItem $customItem
     *
     * @return mixed[]
     *
     * @throws ForbiddenException
     */
    private function defineDeleteButton(int $customObjectId, CustomItem $customItem): array
    {
        $this->permissionProvider->canDelete($customItem);

        return [
            'attr' => [
                'href' => $this->routeProvider->buildDeleteRoute($customObjectId, $customItem->getId()),
            ],
            'btnText'   => 'mautic.core.form.delete',
            'iconClass' => 'fa fa-fw fa-trash-o text-danger',
            'priority'  => 0,
        ];
    }

    /**
     * @param int $customObjectId
     *
     * @return mixed[]
     *
     * @throws ForbiddenException
     */
    private function defineNewButton(int $customObjectId): array
    {
        $this->permissionProvider->canCreate($customObjectId);

        return [
            'attr' => [
                'href' => $this->routeProvider->buildNewRoute($customObjectId),
            ],
            'btnText'   => $this->translator->trans('mautic.core.form.new'),
            'iconClass' => 'fa fa-plus',
            'priority'  => 500,
        ];
    }

    /**
     * @param int $customObjectId
     * @param int $customItemId
     * @param int $contactId
     *
     * @return mixed[]
     *
     * @throws ForbiddenException
     */
    private function defineUnlinkContactButton(int $customObjectId, int $customItemId, int $contactId): array
    {
        $this->permissionProvider->canCreate($customObjectId);

        return [
            'attr' => [
                'href'        => '#',
                'onclick'     => "CustomObjects.unlinkFromContact(this, event, ${customObjectId}, ${contactId});",
                'data-action' => $this->routeProvider->buildUnlinkContactRoute($customItemId, $contactId),
                'data-toggle' => '',
            ],
            'btnText'   => $this->translator->trans('custom.item.unlink'),
            'iconClass' => 'fa fa-unlink',
            'priority'  => 500,
        ];
    }

    /**
     * @param int $customObjectId
     *
     * @return mixed[]
     *
     * @throws ForbiddenException
     */
    private function defineImportNewButton(int $customObjectId): array
    {
        $this->permissionProvider->canCreate($customObjectId);

        return [
            'attr' => [
                'href' => $this->routeProvider->buildNewImportRoute($customObjectId),
            ],
            'btnText'   => $this->translator->trans('mautic.lead.import'),
            'iconClass' => 'fa fa-upload',
            'priority'  => 350,
        ];
    }

    /**
     * @param int $customObjectId
     *
     * @return mixed[]
     *
     * @throws ForbiddenException
     */
    private function defineImportListButton(int $customObjectId): array
    {
        $this->permissionProvider->canCreate($customObjectId);

        return [
            'attr' => [
                'href' => $this->routeProvider->buildListImportRoute($customObjectId),
            ],
            'btnText'   => $this->translator->trans('mautic.lead.lead.import.index'),
            'iconClass' => 'fa fa-history',
            'priority'  => 300,
        ];
    }

    /**
     * @param int $customObjectId
     *
     * @return mixed[]
     *
     * @throws ForbiddenException
     */
    private function defineBatchDeleteButton(int $customObjectId): array
    {
        return [
            'confirm' => [
                'message'       => $this->translator->trans('mautic.core.form.confirmbatchdelete'),
                'confirmAction' => $this->routeProvider->buildBatchDeleteRoute($customObjectId),
                'template'      => 'batchdelete',
            ],
            'btnText'   => 'mautic.core.form.delete',
            'iconClass' => 'fa fa-fw fa-trash-o text-danger',
            'priority'  => 0,
        ];
    }

    /**
     * @param CustomButtonEvent $event
     *
     * @return int
     */
    private function getCustomObjectIdFromEvent(CustomButtonEvent $event): int
    {
        [, $routeParams] = $event->getRoute(true);

        return (int) $routeParams['objectId'];
    }
}
