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

class CustomObjectRouteProvider
{
    public const ROUTE_LIST   = 'mautic_custom_object_list';

    public const ROUTE_VIEW   = 'mautic_custom_object_view';

    public const ROUTE_FORM   = 'mautic_custom_object_form';

    public const ROUTE_CLONE  = 'mautic_custom_object_clone';

    public const ROUTE_DELETE = 'mautic_custom_object_delete';

    public const ROUTE_CANCEL = 'mautic_custom_object_cancel';

    public const ROUTE_SAVE   = 'mautic_custom_object_save';

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
     * @param int $page
     *
     * @return string
     */
    public function buildListRoute(int $page = 1): string
    {
        return $this->router->generate(static::ROUTE_LIST, ['page' => $page]);
    }

    /**
     * @param int|null $id
     *
     * @return string
     */
    public function buildSaveRoute(?int $id = null): string
    {
        $params = $id ? ['objectId' => $id] : [];
        return $this->router->generate(static::ROUTE_SAVE, $params);
    }

    /**
     * @param int $id
     *
     * @return string
     */
    public function buildViewRoute(int $id): string
    {
        return $this->router->generate(static::ROUTE_VIEW, ['objectId' => $id]);
    }

    /**
     * @param int $id
     *
     * @return string
     */
    public function buildFormRoute(int $id = null): string
    {
        $params = $id ? ['objectId' => $id] : [];
        return $this->router->generate(static::ROUTE_FORM, $params);
    }

    /**
     * @param int $id
     *
     * @return string
     */
    public function buildCloneRoute(int $id): string
    {
        return $this->router->generate(static::ROUTE_CLONE, ['objectId' => $id]);
    }

    /**
     * @param int $id
     *
     * @return string
     */
    public function buildDeleteRoute(int $id): string
    {
        return $this->router->generate(static::ROUTE_DELETE, ['objectId' => $id]);
    }
}
