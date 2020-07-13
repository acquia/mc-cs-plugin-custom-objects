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
use Mautic\CoreBundle\Templating\Helper\ButtonHelper;
use MauticPlugin\CustomObjectsBundle\Entity\CustomItem;
use MauticPlugin\CustomObjectsBundle\Exception\ForbiddenException;
use MauticPlugin\CustomObjectsBundle\Provider\CustomItemPermissionProvider;
use MauticPlugin\CustomObjectsBundle\Provider\CustomItemRouteProvider;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Translation\TranslatorInterface;

class CustomItemButtonSubscriber implements EventSubscriberInterface
{
    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * @var CustomItemPermissionProvider
     */
    private $permissionProvider;

    /**
     * @var CustomItemRouteProvider
     */
    private $routeProvider;

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

    public function injectViewButtons(CustomButtonEvent $event): void
    {
        switch ($event->getRoute()) {
            case CustomItemRouteProvider::ROUTE_LIST:
                try {
                    $customObjectId   = $this->getCustomObjectIdFromEvent($event);
                    $filterEntityId   = $event->getRequest()->query->get('filterEntityId', false);
                    $filterEntityType = $event->getRequest()->query->get('filterEntityType', false);
                    $loadedInTab      = (bool) $filterEntityId;
                    if ($loadedInTab && in_array($filterEntityType, ['contact', 'customItem'], true)) {
                        $customItem = $event->getItem();
                        if ($customItem && $customItem instanceof CustomItem) {
                            $event->addButton(
                                $this->defineUnlinkButton($customObjectId, $customItem->getId(), $filterEntityType, (int) $filterEntityId),
                                ButtonHelper::LOCATION_LIST_ACTIONS,
                                $event->getRoute()
                            );

                            if (null !== $customItem->getCustomObject()->getRelationshipObject()) {
                                $event->addButton(
                                    $this->defineEditLinkFormButton($customItem, $customObjectId, $filterEntityType, (int) $filterEntityId),
                                    ButtonHelper::LOCATION_LIST_ACTIONS,
                                    $event->getRoute()
                                );
                            }
                        }
                    } else {
                        $this->addEntityButtons($event, ButtonHelper::LOCATION_LIST_ACTIONS, $customObjectId);
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
                $customObjectId = $this->getCustomObjectIdFromEvent($event);
                $this->addEntityButtons($event, ButtonHelper::LOCATION_PAGE_ACTIONS, $customObjectId);
                $event->addButton(
                    $this->defineCloseButton($customObjectId),
                    ButtonHelper::LOCATION_PAGE_ACTIONS,
                    $event->getRoute()
                );

                break;
        }
    }

    private function addEntityButtons(CustomButtonEvent $event, string $location, int $customObjectId): void
    {
        $customItem = $event->getItem();
        if ($customItem && $customItem instanceof CustomItem) {
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

    private function defineEditLinkFormButton(CustomItem $customItem, int $customObjectId, string $entityType, int $entityId): array
    {
        $this->permissionProvider->canEdit($customItem);

        return [
            'attr' => [
                'href' => $this->routeProvider->buildLinkFormRoute($customItem->getId(), $entityType, $entityId),
                'data-target'               => '#MauticSharedModal',
                'data-toggle'               => 'ajaxmodal',
                'data-header'               => $this->translator->trans('mautic.core.form.edit'),
                'data-modal-open-callback'  => 'customObjectsSetUpLinkFormModalFromEditLink',
                'data-modal-close-callback' => 'customObjectsCleanUpFormModal',
                'data-current-entity-id'    => $entityId,
                'data-current-entity-type'  => $entityType,
                'data-tab-id'               => sprintf('custom-object-%d', $customObjectId),
                'data-custom-object-id'     => $customObjectId
            ],
            'btnText'   => 'mautic.core.form.edit',
            'iconClass' => 'fa fa-pencil-square-o',
            'priority'  => 500,
        ];
    }

    /**
     * @return mixed[]
     */
    private function defineCloseButton(int $customObjectId): array
    {
        return [
            'attr' => [
                'href' => $this->routeProvider->buildListRoute($customObjectId),
            ],
            'btnText'   => 'mautic.core.form.close',
            'iconClass' => 'fa fa-fw fa-remove',
            'priority'  => 400,
        ];
    }

    /**
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
                'data-toggle'           => 'confirmation',
                'data-message'          => $this->translator->trans('custom.item.delete.confirm'),
                'data-confirm-text'     => $this->translator->trans('mautic.core.form.delete'),
                'data-confirm-callback' => 'executeAction',
                'data-cancel-text'      => $this->translator->trans('mautic.core.form.cancel'),
                'data-cancel-callback'  => 'dismissConfirmation',
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
    private function defineNewButton(int $customObjectId): array
    {
        $this->permissionProvider->canCreate($customObjectId);

        return [
            'attr' => [
                'href' => $this->routeProvider->buildNewRoute($customObjectId),
            ],
            'btnText'   => 'mautic.core.form.new',
            'iconClass' => 'fa fa-plus',
            'priority'  => 500,
        ];
    }

    /**
     * @return mixed[]
     *
     * @throws ForbiddenException
     */
    private function defineUnlinkButton(int $customObjectId, int $customItemId, string $entityType, int $entityId): array
    {
        $this->permissionProvider->canCreate($customObjectId);

        return [
            'attr' => [
                'href'        => '#',
                'onclick'     => "CustomObjects.unlinkCustomItemFromEntity(this, event, ${customObjectId}, '${entityType}', ${entityId}, 'custom-object-${customObjectId}');",
                'data-action' => $this->routeProvider->buildUnlinkRoute($customItemId, $entityType, $entityId),
                'data-toggle' => '',
            ],
            'btnText'   => $this->translator->trans('custom.item.unlink'),
            'iconClass' => 'fa fa-unlink',
            'priority'  => 500,
        ];
    }

    /**
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
            'btnText'   => 'mautic.lead.import',
            'iconClass' => 'fa fa-upload',
            'priority'  => 350,
        ];
    }

    /**
     * @return mixed[]
     *
     * @throws ForbiddenException
     */
    private function defineImportListButton(int $customObjectId): array
    {
        $this->permissionProvider->canViewAtAll($customObjectId);

        return [
            'attr' => [
                'href' => $this->routeProvider->buildListImportRoute($customObjectId),
            ],
            'btnText'   => 'mautic.lead.lead.import.index',
            'iconClass' => 'fa fa-history',
            'priority'  => 300,
        ];
    }

    /**
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

    private function getCustomObjectIdFromEvent(CustomButtonEvent $event): int
    {
        [, $routeParams] = $event->getRoute(true);

        return (int) $routeParams['objectId'];
    }
}
