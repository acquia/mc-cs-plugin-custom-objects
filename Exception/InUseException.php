<?php

declare(strict_types=1);

namespace MauticPlugin\CustomObjectsBundle\Exception;

class InUseException extends \Exception
{
    private array $segmentList = [];

    public function setSegmentList(array $segmentList): void
    {
        $this->segmentList = $segmentList;
    }

    public function getSegmentList(): array
    {
        return $this->segmentList;
    }
}
