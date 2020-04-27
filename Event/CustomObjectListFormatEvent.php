<?php

declare(strict_types=1);

namespace MauticPlugin\CustomObjectsBundle\Event;

use Symfony\Component\EventDispatcher\Event;

class CustomObjectListFormatEvent extends Event
{
    /**
     * @var array $customObjectValues
     */
    private $customObjectValues;

    /**
     * @var string $format
     */
    private $format;

    /**
     * @var string $formattedString
     */
    private $formattedString = '';

    /**
     * @var bool $hasBeenFormatted
     */
    private $hasBeenFormatted = false;

    public function __construct(array $customObjectValues, string $format = 'default')
    {
        $this->customObjectValues = $customObjectValues;
        $this->format = $format;
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
            $this->formattedString = $formattedString;
        }
    }

    public function hasBeenFormatted(): bool
    {
        return $this->hasBeenFormatted;
    }
}
