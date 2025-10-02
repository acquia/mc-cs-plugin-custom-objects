<?php

declare(strict_types=1);

namespace MauticPlugin\CustomObjectsBundle\Exception;

class NotFoundException extends \Exception
{
    public function __construct(
        string $message = 'Not found',
        int $code = 404,
        ?\Throwable $throwable = null
    ) {
        parent::__construct($message, $code, $throwable);
    }
}
