<?php

declare(strict_types=1);

namespace MauticPlugin\CustomObjectsBundle\Provider;

use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Symfony\Component\HttpFoundation\Session\Session;

class SessionProviderFactory
{
    /**
     * @var Session
     */
    private $session;

    /**
     * @var CoreParametersHelper
     */
    private $coreParametersHelper;

    public function __construct(Session $session, CoreParametersHelper $coreParametersHelper)
    {
        $this->session              = $session;
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
        return new SessionProvider($this->session, $namespace, (int) $this->coreParametersHelper->get('default_pagelimit'));
    }
}
