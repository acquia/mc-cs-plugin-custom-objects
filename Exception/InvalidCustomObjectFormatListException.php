<?php

declare(strict_types=1);

namespace MauticPlugin\CustomObjectsBundle\Exception;

class InvalidCustomObjectFormatListException extends \Exception
{
    public function __construct(string $format)
    {
        $message = sprintf("'%s' is not a valid custom object list format.", $format);
        parent::__construct($message);
    }
}
