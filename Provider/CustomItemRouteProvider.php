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

namespace MauticPlugin\CustomObjectsBundle\Provider;

use Symfony\Component\Routing\RouterInterface;

class CustomItemRouteProvider
{
    public const ROUTE_LIST          = 'mautic_custom_item_list';

    public const ROUTE_VIEW          = 'mautic_custom_item_view';

    public const ROUTE_EDIT          = 'mautic_custom_item_edit';

    public const ROUTE_CLONE         = 'mautic_custom_item_clone';

    public const ROUTE_DELETE        = 'mautic_custom_item_delete';

    public const ROUTE_BATCH_DELETE  = 'mautic_custom_item_batch_delete';

    public const ROUTE_NEW           = 'mautic_custom_item_new';

    public const ROUTE_CANCEL        = 'mautic_custom_item_cancel';

    public const ROUTE_SAVE          = 'mautic_custom_item_save';

    public const ROUTE_LOOKUP        = 'mautic_custom_item_lookup';

    public const ROUTE_LINK          = 'mautic_custom_item_link';

    public const ROUTE_UNLINK        = 'mautic_custom_item_unlink';

    public const ROUTE_IMPORT_ACTION = 'mautic_import_action';

    public const ROUTE_IMPORT_LIST   = 'mautic_import_index';

    public const ROUTE_CONTACT_LIST  = 'mautic_custom_item_contacts';

    /**
     * @var RouterInterface
     */
    private $router;

    /**
     * @param RouterInterface $router
     */
    public function __construct(RouterInterface $router)
    {
        $this->router = $router;
    }

    /**
     * @param int $objectId
     * @param int $page
     *
     * @return string
     */
    public function buildListRoute(int $objectId, int $page = 1): string
    {
        return $this->router->generate(static::ROUTE_LIST, ['objectId' => $objectId, 'page' => $page]);
    }

    /**
     * @param int $customItemId
     * @param int $contactId
     *
     * @return string
     */
    public function buildUnlinkContactRoute(int $customItemId, int $contactId): string
    {
        return $this->router->generate(static::ROUTE_UNLINK, ['itemId' => $customItemId, 'entityType' => 'contact', 'entityId' => $contactId]);
    }

    /**
     * @param int $objectId
     *
     * @return string
     */
    public function buildNewRoute(int $objectId): string
    {
        return $this->router->generate(static::ROUTE_NEW, ['objectId' => $objectId]);
    }

    /**
     * @param int      $objectId
     * @param int|null $itemId
     *
     * @return string
     */
    public function buildSaveRoute(int $objectId, ?int $itemId = null): string
    {
        return $this->router->generate(static::ROUTE_SAVE, ['objectId' => $objectId, 'itemId' => $itemId]);
    }

    /**
     * @param int $itemId
     *
     * @return string
     */
    public function buildViewRoute(int $objectId, int $itemId): string
    {
        return $this->router->generate(static::ROUTE_VIEW, ['objectId' => $objectId, 'itemId' => $itemId]);
    }

    /**
     * @param int $itemId
     *
     * @return string
     */
    public function buildEditRoute(int $objectId, int $itemId): string
    {
        return $this->router->generate(static::ROUTE_EDIT, ['objectId' => $objectId, 'itemId' => $itemId]);
    }

    /**
     * @param int $itemId
     */
    public function buildCloneRoute(int $objectId, int $itemId): string
    {
        return $this->router->generate(static::ROUTE_CLONE, ['objectId' => $objectId, 'itemId' => $itemId]);
    }

    /**
     * @param int $itemId
     *
     * @return string
     */
    public function buildDeleteRoute(int $objectId, int $itemId): string
    {
        return $this->router->generate(static::ROUTE_DELETE, ['objectId' => $objectId, 'itemId' => $itemId]);
    }

    /**
     * @param int $objectId
     *
     * @return string
     */
    public function buildLookupRoute(int $objectId): string
    {
        return $this->router->generate(static::ROUTE_LOOKUP, ['objectId' => $objectId]);
    }

    /**
     * @param int $objectId
     *
     * @return string
     */
    public function buildBatchDeleteRoute(int $objectId): string
    {
        return $this->router->generate(static::ROUTE_BATCH_DELETE, ['objectId' => $objectId]);
    }

    /**
     * @param int $objectId
     *
     * @return string
     */
    public function buildNewImportRoute(int $objectId): string
    {
        return $this->router->generate(static::ROUTE_IMPORT_ACTION, [
            'object'       => $this->buildImportRouteObject($objectId),
            'objectAction' => 'new',
        ]);
    }

    /**
     * @param int $objectId
     *
     * @return string
     */
    public function buildListImportRoute(int $objectId): string
    {
        return $this->router->generate(static::ROUTE_IMPORT_LIST, ['object' => $this->buildImportRouteObject($objectId)]);
    }

    /**
     * @param int $objectId
     *
     * @return string
     */
    private function buildImportRouteObject(int $objectId): string
    {
        return "custom-object:{$objectId}";
    }
}
