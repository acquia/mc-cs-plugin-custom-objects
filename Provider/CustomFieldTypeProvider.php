<?php

declare(strict_types=1);

/*
 * @copyright   2018 Mautic, Inc. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.com
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

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
     * @param string $key
     *
     * @return CustomFieldTypeInterface
     *
     * @throws NotFoundException
     */
    public function getType(string $key): CustomFieldTypeInterface
    {
        if (isset($this->customFieldTypes[$key])) {
            return $this->customFieldTypes[$key];
        }

        throw new NotFoundException("Field type '{$key}' does not exist.");
    }

    /**
     * @param CustomFieldTypeInterface $customFieldType
     */
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
            /** @var AbstractCustomFieldType $key */
            $mapping[$key::NAME] = $val;
        });

        return $mapping;
    }
}
