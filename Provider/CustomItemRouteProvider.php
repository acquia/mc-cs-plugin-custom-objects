<?php

declare(strict_types=1);

namespace MauticPlugin\CustomObjectsBundle\Provider;

use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;

class CustomItemRouteProvider
{
    public const ROUTE_LIST          = 'mautic_custom_item_list';

    public const ROUTE_VIEW          = 'mautic_custom_item_view';

    public const ROUTE_EDIT          = 'mautic_custom_item_edit';

    public const ROUTE_NEW_REDIRECT_TO_CONTACT = 'mautic_custom_item_edit_redirect_to_contact';

    public const ROUTE_CLONE         = 'mautic_custom_item_clone';

    public const ROUTE_DELETE        = 'mautic_custom_item_delete';

    public const ROUTE_BATCH_DELETE  = 'mautic_custom_item_batch_delete';

    public const ROUTE_NEW           = 'mautic_custom_item_new';

    public const ROUTE_EDIT_REDIRECT_TO_CONTACT = 'mautic_custom_item_new_redirect_to_contact';

    public const ROUTE_CANCEL        = 'mautic_custom_item_cancel';

    public const ROUTE_SAVE          = 'mautic_custom_item_save';

    public const ROUTE_LOOKUP        = 'mautic_custom_item_lookup';

    public const ROUTE_LINK          = 'mautic_custom_item_link';

    public const ROUTE_LINK_FORM     = 'mautic_custom_item_link_form';

    public const ROUTE_LINK_FORM_SAVE  = 'mautic_custom_item_link_form_save';

    public const ROUTE_UNLINK        = 'mautic_custom_item_unlink';

    public const ROUTE_IMPORT_ACTION = 'mautic_import_action';

    public const ROUTE_IMPORT_LIST   = 'mautic_import_index';

    public const ROUTE_EXPORT_ACTION   = 'mautic_export_action';

    public const ROUTE_EXPORT_DOWNLOAD_ACTION   = 'mautic_export_download_action';

    public const ROUTE_CONTACT_LIST  = 'mautic_custom_item_contacts';

    /**
     * @var RouterInterface
     */
    private $router;

    public function __construct(RouterInterface $router)
    {
        $this->router = $router;
    }

    public function buildListRoute(int $objectId, int $page = 1, string $filterEntityType = null, int $filterEntityId = null, array $parameters = []): string
    {
        return $this->router->generate(static::ROUTE_LIST, array_merge([
            'objectId'         => $objectId,
            'page'             => $page,
            'filterEntityType' => $filterEntityType,
            'filterEntityId'   => $filterEntityId,
        ], $parameters));
    }

    public function buildLinkRoute(int $customItemId, string $entityType, int $entityId): string
    {
        return $this->router->generate(
            static::ROUTE_LINK,
            ['itemId' => $customItemId, 'entityType' => $entityType, 'entityId' => $entityId]
        );
    }

    public function buildUnlinkRoute(int $customItemId, string $entityType, int $entityId): string
    {
        return $this->router->generate(
            static::ROUTE_UNLINK,
            ['itemId' => $customItemId, 'entityType' => $entityType, 'entityId' => $entityId]
        );
    }

    public function buildNewRoute(int $objectId): string
    {
        return $this->router->generate(static::ROUTE_NEW, ['objectId' => $objectId]);
    }

    public function buildNewRouteWithRedirectToContact(int $objectId, int $contactId): string
    {
        return $this->router->generate(static::ROUTE_NEW_REDIRECT_TO_CONTACT, ['objectId' => $objectId, 'contactId' => $contactId]);
    }

    public function buildSaveRoute(int $objectId, int $itemId = null): string
    {
        return $this->router->generate(static::ROUTE_SAVE, ['objectId' => $objectId, 'itemId' => $itemId]);
    }

    public function buildViewRoute(int $objectId, int $itemId): string
    {
        return $this->router->generate(static::ROUTE_VIEW, ['objectId' => $objectId, 'itemId' => $itemId]);
    }

    public function buildEditRoute(int $objectId, int $itemId): string
    {
        return $this->router->generate(static::ROUTE_EDIT, ['objectId' => $objectId, 'itemId' => $itemId]);
    }

    public function buildEditRouteWithRedirectToContact(int $objectId, int $itemId, int $contactId): string
    {
        return $this->router->generate(static::ROUTE_EDIT_REDIRECT_TO_CONTACT, ['objectId' => $objectId, 'itemId' => $itemId, 'contactId' => $contactId]);
    }

    public function buildCloneRoute(int $objectId, int $itemId): string
    {
        return $this->router->generate(static::ROUTE_CLONE, ['objectId' => $objectId, 'itemId' => $itemId]);
    }

    public function buildDeleteRoute(int $objectId, int $itemId): string
    {
        return $this->router->generate(static::ROUTE_DELETE, ['objectId' => $objectId, 'itemId' => $itemId]);
    }

    public function buildContactViewRoute(int $contactId): string
    {
        return $this->router->generate('mautic_contact_action', ['objectAction' => 'view', 'objectId' => $contactId]);
    }

    public function buildLookupRoute(int $objectId, string $entityType = null, int $entityId = null): string
    {
        return $this->router->generate(
            static::ROUTE_LOOKUP,
            ['objectId' => $objectId, 'filterEntityType' => $entityType, 'filterEntityId' => $entityId]
        );
    }

    public function buildBatchDeleteRoute(int $objectId): string
    {
        return $this->router->generate(static::ROUTE_BATCH_DELETE, ['objectId' => $objectId]);
    }

    public function buildLinkFormRoute(int $itemId, string $entityType, int $entityId): string
    {
        return $this->router->generate(static::ROUTE_LINK_FORM, ['itemId' => $itemId, 'entityType' => $entityType, 'entityId' => $entityId]);
    }

    public function buildLinkFormSaveRoute(int $itemId, string $entityType, int $entityId): string
    {
        return $this->router->generate(static::ROUTE_LINK_FORM_SAVE, ['itemId' => $itemId, 'entityType' => $entityType, 'entityId' => $entityId]);
    }

    public function buildNewImportRoute(int $objectId): string
    {
        return $this->router->generate(static::ROUTE_IMPORT_ACTION, [
            'object'       => $this->buildImportOrExportRouteObject($objectId),
            'objectAction' => 'new',
        ]);
    }

    public function buildListImportRoute(int $objectId): string
    {
        return $this->router->generate(static::ROUTE_IMPORT_LIST, ['object' => $this->buildImportOrExportRouteObject($objectId)]);
    }

    public function buildExportRoute(int $objectId): string
    {
        return $this->router->generate(static::ROUTE_EXPORT_ACTION, ['object' => $objectId]);
    }

    public function buildExportDownloadRoute(string $fileName): string
    {
        return $this->router->generate(static::ROUTE_EXPORT_DOWNLOAD_ACTION, ['fileName' => $fileName], UrlGeneratorInterface::ABSOLUTE_URL);
    }

    private function buildImportOrExportRouteObject(int $objectId): string
    {
        return "custom-object:{$objectId}";
    }
}
