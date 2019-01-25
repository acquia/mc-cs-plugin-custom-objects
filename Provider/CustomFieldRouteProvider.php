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

use MauticPlugin\CustomObjectsBundle\Exception\ForbiddenException;
use Symfony\Component\Routing\RouterInterface;

class CustomFieldRouteProvider
{
    public const ROUTE_FORM   = 'mautic_custom_field_form';
    public const ROUTE_CLONE  = 'mautic_custom_field_clone';
    public const ROUTE_DELETE = 'mautic_custom_field_delete';
    public const ROUTE_CANCEL = 'mautic_custom_field_cancel';
    public const ROUTE_SAVE   = 'mautic_custom_field_save';

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
     * @param int|null $id
     *
     * @return string
     */
    public function buildSaveRoute(?int $id = null): string
    {
        return $this->router->generate(static::ROUTE_SAVE, ['fieldId' => $id]);
    }

    /**
     * @param int|null $id
     *
     * @return string
     * @throws ForbiddenException
     */
    public function buildFormRoute(int $id = null): string
    {
        $params = $id ? ['fieldId' => $id] : [];
        return $this->router->generate(static::ROUTE_FORM, $params);
    }

    /**
     * @param int $id
     *
     * @return string
     * @throws ForbiddenException
     */
    public function buildCloneRoute(int $id): string
    {
        return $this->router->generate(static::ROUTE_CLONE, ['objectId' => $id]);
    }

    /**
     * @param int $id
     *
     * @return string
     * @throws ForbiddenException
     */
    public function buildDeleteRoute(int $id): string
    {
        return $this->router->generate(static::ROUTE_DELETE, ['fieldId' => $id]);
    }
}
