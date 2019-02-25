<?php

declare(strict_types=1);

/*
 * @copyright   2018 Mautic Inc. All rights reserved
 *
 * @author      Mautic, Inc.
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\CustomObjectsBundle\Exception;

use Exception;
use Throwable;

class NotFoundException extends Exception
{
    /**
     * @param string         $message
     * @param int            $code
     * @param Throwable|null $throwable
     */
    public function __construct(
        string $message = 'Not found',
        int $code = 404,
        ?Throwable $throwable = null
    ) {
        parent::__construct($message, $code, $throwable);
    }
}
