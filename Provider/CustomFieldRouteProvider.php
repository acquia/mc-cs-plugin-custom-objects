<?php

declare(strict_types=1);

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

    public function __construct(RouterInterface $router)
    {
        $this->router = $router;
    }

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

    public function buildFormRoute(?int $id = null): string
    {
        $params = $id ? ['fieldId' => $id] : [];

        return $this->router->generate(static::ROUTE_FORM, $params);
    }
}
