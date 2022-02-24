<?php

declare(strict_types=1);

namespace MauticPlugin\CustomObjectsBundle\Exception;

use Exception;
use Throwable;

class ForbiddenException extends Exception
{
    /**
     * @param string $entityType
     * @param int    $entityId
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
