<?php

declare(strict_types=1);

namespace MauticPlugin\CustomObjectsBundle\Event;

use Symfony\Contracts\EventDispatcher\Event;

class CustomObjectListFormatEvent extends Event
{
    private string $formattedString = '';

    private bool $hasBeenFormatted = false;

    public function __construct(private array $customObjectValues, private string $format = 'default')
    {
    }

    public function getCustomObjectValues(): array
    {
        return $this->customObjectValues;
    }

    public function getFormat(): string
    {
        return $this->format;
    }

    public function getFormattedString(): string
    {
        return $this->formattedString;
    }

    public function setFormattedString(string $formattedString): void
    {
        if ('' !== $formattedString) {
            $this->hasBeenFormatted = true;
            $this->formattedString  = $formattedString;
        }
    }

    public function hasBeenFormatted(): bool
    {
        return $this->hasBeenFormatted;
    }
}
