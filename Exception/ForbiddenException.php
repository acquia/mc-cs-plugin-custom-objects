<?php

declare(strict_types=1);

/*
 * @copyright   2018 Mautic Inc. All rights reserved
 *
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.com
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\CustomObjectsBundle\Exception;

use Exception;
use Throwable;

class ForbiddenException extends Exception
{
    /**
     * @param string         $permission
     * @param string         $entityType
     * @param int            $entityId
     * @param int            $code
     * @param Throwable|null $throwable
     */
    public function __construct(
        string $permission,
        ?string $entityType = null,
        ?int $entityId = null,
        int $code = 403,
        ?Throwable $throwable = null
    ) {
        parent::__construct(
            trim("You do not have permission to {$permission} {$entityType} {$entityId}"),
            $code,
            $throwable
        );
    }
}
