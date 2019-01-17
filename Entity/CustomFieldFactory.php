<?php

/*
 * @copyright   2019 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\CustomObjectsBundle\Entity;

class CustomFieldFactory
{
    private const CUSTOM_FIELD_CLASS_NAME_PATTERN = 'MauticPlugin\CustomObjectsBundle\CustomFieldType\%sType';

    /**
     * @param string $type
     *
     * @return CustomField
     */
    public function create(string $type): CustomField
    {
        $customField = new CustomField();

        $typeClass = sprintf(self::CUSTOM_FIELD_CLASS_NAME_PATTERN, ucfirst($type));

        try {
            $type = new $typeClass('type');
        } catch (\Error $e) {
            throw new \InvalidArgumentException(
                sprintf("Undefined custom field type '%s'", $type)
            );
        }

        $customField->setType($type);

        return $customField;
    }
}