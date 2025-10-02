<?php

declare(strict_types=1);

namespace MauticPlugin\CustomObjectsBundle\Event;

use Mautic\CoreBundle\Service\FlashBag;
use MauticPlugin\CustomObjectsBundle\Entity\CustomObject;
use Symfony\Contracts\EventDispatcher\Event;

class CustomObjectEvent extends Event
{
    private string $message;

    private FlashBag $flashBag;

    public function __construct(private CustomObject $customObject, private bool $isNew = false)
    {
    }

    public function getCustomObject(): CustomObject
    {
        return $this->customObject;
    }

    public function entityIsNew(): bool
    {
        return $this->isNew;
    }

    public function getMessage(): string
    {
        return (string) $this->message;
    }

    public function setMessage(string $message): void
    {
        $this->message = $message;
    }

    public function getFlashBag(): ?FlashBag
    {
        return $this->flashBag;
    }

    public function setFlashBag(FlashBag $flashBag): void
    {
        $this->flashBag = $flashBag;
    }
}
