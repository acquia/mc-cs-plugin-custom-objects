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

class CustomFieldRouteProvider
{
    public const ROUTE_FORM   = 'mautic_custom_field_form';

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
     * @param string   $fieldType
     * @param int|null $id
     * @param int|null $objectId
     * @param int|null $panelCount
     * @param int|null $panelId
     *
     * @return string
     */
    public function buildSaveRoute(string $fieldType, ?int $id = null, ?int $objectId = null, ?int $panelCount = null, ?int $panelId = null): string
    {
        $params['fieldType'] = $fieldType;

        if ($id) {
            $params['fieldId'] = $id;
        }

        if ($objectId) {
            $params['objectId'] = $objectId;
        }

        if ($panelCount) {
            $params['panelCount'] = $panelCount;
        }

        if (null !== $panelId) {
            $params['panelId'] = $panelId;
        }

        return $this->router->generate(static::ROUTE_SAVE, $params);
    }

    /**
     * @param int|null $id
     *
     * @return string
     */
    public function buildFormRoute(?int $id = null): string
    {
        $params = $id ? ['fieldId' => $id] : [];

        return $this->router->generate(static::ROUTE_FORM, $params);
    }
}
