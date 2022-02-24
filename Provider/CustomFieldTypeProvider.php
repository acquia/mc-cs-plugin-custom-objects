<?php

declare(strict_types=1);

namespace MauticPlugin\CustomObjectsBundle\Provider;

use MauticPlugin\CustomObjectsBundle\CustomFieldType\AbstractCustomFieldType;
use MauticPlugin\CustomObjectsBundle\CustomFieldType\CustomFieldTypeInterface;
use MauticPlugin\CustomObjectsBundle\Exception\NotFoundException;

class CustomFieldTypeProvider
{
    /**
     * @var CustomFieldTypeInterface[]
     */
    private $customFieldTypes = [];

    /**
     * Builds the list of custom field type objects.
     *
     * @return CustomFieldTypeInterface[]
     */
    public function getTypes(): array
    {
        return $this->customFieldTypes;
    }

    /**
     * @throws NotFoundException
     */
    public function getType(string $key): CustomFieldTypeInterface
    {
        if (isset($this->customFieldTypes[$key])) {
            return $this->customFieldTypes[$key];
        }

        throw new NotFoundException("Field type '{$key}' does not exist.");
    }

    public function addType(CustomFieldTypeInterface $customFieldType): void
    {
        $this->customFieldTypes[$customFieldType->getKey()] = $customFieldType;
    }

    /**
     * @return mixed[]
     */
    public function getKeyTypeMapping(): array
    {
        $mapping = [];
        $types   = $this->getTypes();

        array_walk($types, function ($key, $val) use (&$mapping): void {
            /* @var AbstractCustomFieldType $key */
            $mapping[$key::NAME] = $val;
        });

        return $mapping;
    }
}
