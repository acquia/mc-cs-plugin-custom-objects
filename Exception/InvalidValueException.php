<?php

declare(strict_types=1);

namespace MauticPlugin\CustomObjectsBundle\Exception;

use Exception;
use MauticPlugin\CustomObjectsBundle\Entity\CustomField;

class InvalidValueException extends Exception
{
    /**
     * @var CustomField|null
     */
    private $customField;

    public function setCustomField(CustomField $customField): void
    {
        $this->customField = $customField;
    }

    public function getCustomField(): ?CustomField
    {
        return $this->customField;
    }
}
