<?php

declare(strict_types=1);

namespace MauticPlugin\CustomObjectsBundle\Exception;

use Exception;
use Throwable;

class NoRelationshipException extends Exception
{
    public function __construct(
        int $code = 403,
        ?Throwable $throwable = null
    ) {
        parent::__construct(
            'This custom object does not have relationship fields defined.',
            $code,
            $throwable
        );
    }
}
