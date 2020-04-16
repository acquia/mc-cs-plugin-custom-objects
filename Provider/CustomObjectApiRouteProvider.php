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

class CustomObjectApiRouteProvider
{
    public const ROUTE_LIST   = 'mautic_customapi_object_list';

    public const ROUTE_VIEW   = 'mautic_customapi_object_view';

    public const ROUTE_NEW    = 'mautic_customapi_object_new';

    public const ROUTE_EDIT   = 'mautic_customapi_object_edit';

    public const ROUTE_DELETE = 'mautic_customapi_object_delete';

    public const ROUTE_SAVE   = 'mautic_customapi_object_save';

    /**
     * @var RouterInterface
     */
    private $router;

    public function __construct(RouterInterface $router)
    {
        $this->router = $router;
    }

    public function buildListRoute(int $page = 1): string
    {
        return $this->router->generate(static::ROUTE_LIST, ['page' => $page]);
    }

    public function buildSaveRoute(?int $id = null): string
    {
        return $this->router->generate(static::ROUTE_SAVE, ['objectId' => $id]);
    }

    public function buildViewRoute(int $id): string
    {
        return $this->router->generate(static::ROUTE_VIEW, ['objectId' => $id]);
    }

    public function buildNewRoute(): string
    {
        return $this->router->generate(static::ROUTE_NEW);
    }

    /**
     * @param int $id
     */
    public function buildEditRoute(?int $id = null): string
    {
        return $this->router->generate(static::ROUTE_EDIT, ['objectId' => $id]);
    }

    public function buildDeleteRoute(int $id): string
    {
        return $this->router->generate(static::ROUTE_DELETE, ['objectId' => $id]);
    }
}
