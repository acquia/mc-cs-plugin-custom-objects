<?php

declare(strict_types=1);

/*
 * @copyright   2019 Mautic, Inc. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.com
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\CustomObjectsBundle\Provider;

use Symfony\Component\HttpFoundation\Session\Session;

class CustomItemSessionProvider
{
    public const KEY_PAGE = 'custom.item.page';

    /**
     * @var Session
     */
    private $session;

    /**
     * @param Session $session
     */
    public function __construct(Session $session)
    {
        $this->session = $session;
    }

    /**
     * @param int $default
     * 
     * @return int
     */
    public function getPage(int $default = 1): int
    {
        return $this->session->get(self::KEY_PAGE, $default);
    }

    /**
     * @param string $message
     * @param string $type
     */
    public function addFlash(string $message, string $type = 'notice'): void
    {
        $this->session->getFlashBag()->add($type, $message);
    }
}
