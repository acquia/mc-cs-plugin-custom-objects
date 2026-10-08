<?php

declare(strict_types=1);

namespace MauticPlugin\CustomObjectsBundle\Event;

use MauticPlugin\CustomObjectsBundle\Entity\CustomItemExportScheduler;
use Symfony\Contracts\EventDispatcher\Event;

class CustomItemExportSchedulerEvent extends Event
{
    private CustomItemExportScheduler $customItemExportScheduler;

    private string $filePath;

    public function __construct(CustomItemExportScheduler $customItemExportScheduler)
    {
        $this->customItemExportScheduler = $customItemExportScheduler;
    }

    public function getCustomItemExportScheduler(): CustomItemExportScheduler
    {
        return $this->customItemExportScheduler;
    }

    public function getFilePath(): string
    {
        return $this->filePath;
    }

    public function setFilePath(string $filePath): void
    {
        $this->filePath = $filePath;
    }
}
