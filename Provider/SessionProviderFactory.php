<?php

declare(strict_types=1);

namespace MauticPlugin\CustomObjectsBundle\Provider;

use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Symfony\Component\HttpFoundation\RequestStack;

class SessionProviderFactory
{
    /**
     * @var RequestStack
     */
    private $requestStack;

    /**
     * @var CoreParametersHelper
     */
    private $coreParametersHelper;

    public function __construct(RequestStack $requestStack, CoreParametersHelper $coreParametersHelper)
    {
        $this->requestStack = $requestStack;
        $this->coreParametersHelper = $coreParametersHelper;
    }

    public function createObjectProvider(): SessionProvider
    {
        return $this->createProvider('custom-object');
    }

    public function createItemProvider(int $objectId, string $filterEntityType = null, int $filterEntityId = null, bool $lookup = false): SessionProvider
    {
        $namespace = implode('-', ['custom-item', $objectId, $filterEntityType, $filterEntityId, (int) $lookup]);

        return $this->createProvider($namespace);
    }

    private function createProvider(string $namespace): SessionProvider
    {
        return new SessionProvider($this->requestStack, $namespace, (int) $this->coreParametersHelper->get('default_pagelimit'));
    }
}
