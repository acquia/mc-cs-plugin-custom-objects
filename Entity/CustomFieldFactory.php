<?php

declare(strict_types=1);

namespace MauticPlugin\CustomObjectsBundle\Entity;

use MauticPlugin\CustomObjectsBundle\Entity\CustomField\Params;
use MauticPlugin\CustomObjectsBundle\Exception\NotFoundException;
use MauticPlugin\CustomObjectsBundle\Provider\CustomFieldTypeProvider;

class CustomFieldFactory
{
    /**
     * @var CustomFieldTypeProvider
     */
    private $customFieldTypeProvider;

    public function __construct(CustomFieldTypeProvider $customFieldTypeProvider)
    {
        $this->customFieldTypeProvider = $customFieldTypeProvider;
    }

    /**
     * @throws NotFoundException
     */
    public function create(string $type, CustomObject $customObject): CustomField
    {
        $typeObject = $this->customFieldTypeProvider->getType($type);

        $customField = new CustomField();

        $customField->setType($type);
        $customField->setTypeObject($typeObject);
        $customField->setCustomObject($customObject);
        $customField->setParams(new Params());

        return $customField;
    }
}
