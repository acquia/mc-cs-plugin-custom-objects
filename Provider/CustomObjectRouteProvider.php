<?php

declare(strict_types=1);

namespace MauticPlugin\CustomObjectsBundle\Provider;

use Symfony\Component\Routing\RouterInterface;

class CustomObjectRouteProvider
{
    public const ROUTE_LIST   = 'mautic_custom_object_list';

    public const ROUTE_VIEW   = 'mautic_custom_object_view';

    public const ROUTE_NEW    = 'mautic_custom_object_new';

    public const ROUTE_EDIT   = 'mautic_custom_object_edit';

    public const ROUTE_CLONE  = 'mautic_custom_object_clone';

    public const ROUTE_DELETE = 'mautic_custom_object_delete';

    public const ROUTE_CANCEL = 'mautic_custom_object_cancel';

    public const ROUTE_SAVE   = 'mautic_custom_object_save';

    public const ROUTE_LINK   = 'mautic_custom_object_link';

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

    public function buildCloneRoute(int $id): string
    {
        return $this->router->generate(static::ROUTE_CLONE, ['objectId' => $id]);
    }

    public function buildDeleteRoute(int $id): string
    {
        return $this->router->generate(static::ROUTE_DELETE, ['objectId' => $id]);
    }
}
