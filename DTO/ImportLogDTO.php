<?php

declare(strict_types=1);

namespace MauticPlugin\CustomObjectsBundle\DTO;

class ImportLogDTO
{
    private array $warnings = [];

    public function hasWarning(): bool
    {
        return !empty($this->warnings);
    }

    public function addWarning(string $warning): void
    {
        $this->warnings[] = $warning;
    }

    public function getFormattedWarning(): string
    {
        return implode('\n', $this->warnings);
    }
}
