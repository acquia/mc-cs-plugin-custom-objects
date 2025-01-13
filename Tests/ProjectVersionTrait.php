<?php

declare(strict_types=1);

namespace MauticPlugin\CustomObjectsBundle\Tests;

trait ProjectVersionTrait
{
    private function isCloudProject(): bool
    {
        return str_starts_with((string) getenv('MAUTIC_PROJECT_VERSION'), 'cloud-');
    }
}
