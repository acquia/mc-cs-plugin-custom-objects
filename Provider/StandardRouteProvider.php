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

abstract class StandardRouteProvider
{
    public const ROUTE_LIST   = 'undefined';
    public const ROUTE_VIEW   = 'undefined';
    public const ROUTE_EDIT   = 'undefined';
    public const ROUTE_CLONE  = 'undefined';
    public const ROUTE_DELETE = 'undefined';
    public const ROUTE_NEW    = 'undefined';
    public const ROUTE_CANCEL = 'undefined';
    public const ROUTE_SAVE   = 'undefined';

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
     * @throws ForbiddenException
     */
    public function buildListRoute(int $page = 1): string
    {
        return $this->router->generate(static::ROUTE_LIST, ['page' => $page]);
    }

    /**
     * @param array $params
     *
     * @return string
     */
    public function buildNewRoute(array $params = []): string
    {
        return $this->router->generate(static::ROUTE_NEW, $params);
    }

    /**
     * @throws ForbiddenException
     */
    public function buildSaveRoute(?int $id = null): string
    {
        return $this->router->generate(static::ROUTE_SAVE, ['objectId' => $id]);
    }

    /**
     * @param int $id
     * 
     * @throws ForbiddenException
     */
    public function buildViewRoute(int $id): string
    {
        return $this->router->generate(static::ROUTE_VIEW, ['objectId' => $id]);
    }

    /**
     * @param int $id
     * 
     * @throws ForbiddenException
     */
    public function buildEditRoute(int $id): string
    {
        return $this->router->generate(static::ROUTE_EDIT, ['objectId' => $id]);
    }

    /**
     * @param int $id
     * 
     * @throws ForbiddenException
     */
    public function buildCloneRoute(int $id): string
    {
        return $this->router->generate(static::ROUTE_CLONE, ['objectId' => $id]);
    }

    /**
     * @param int $id
     * 
     * @throws ForbiddenException
     */
    public function buildDeleteRoute(int $id): string
    {
        return $this->router->generate(static::ROUTE_DELETE, ['objectId' => $id]);
    }
}
