<?php

declare(strict_types=1);

namespace MauticPlugin\CustomObjectsBundle\DTO;

class ImportLogDTO
{
    /**
     * @var array<string>
     */
    private array $warnings = [];

    public function hasWarning(): bool
    {
        return !empty($this->warnings);
    }

    public function addWarning(string $warning): void
    {
        $this->warnings[] = $warning;
    }

    public function getWarningsAsString(): string
    {
        return implode('\n', $this->warnings);
    }
}
