<?php

declare(strict_types=1);

/*
 * @copyright   2020 Mautic, Inc. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.com
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

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
        return $this->createProvider('mautic.custom.object');
    }

    public function createItemProvider(int $objectId, string $filterEntityType = null, int $filterEntityId = null): SessionProvider
    {
        $namespace = implode('.', array_filter(['mautic.custom.item', $objectId, $filterEntityType, $filterEntityId]));

        return $this->createProvider($namespace);
    }

    private function createProvider(string $namespace): SessionProvider
    {
        return new SessionProvider($this->session, $namespace, (int) $this->coreParametersHelper->get('default_pagelimit'));
    }
}
