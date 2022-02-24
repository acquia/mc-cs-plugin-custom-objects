<?php

declare(strict_types=1);

namespace MauticPlugin\CustomObjectsBundle\Exception;

use Exception;
use Throwable;

class NotFoundException extends Exception
{
    public function __construct(
        string $message = 'Not found',
        int $code = 404,
        ?Throwable $throwable = null
    ) {
        parent::__construct($message, $code, $throwable);
    }
}
