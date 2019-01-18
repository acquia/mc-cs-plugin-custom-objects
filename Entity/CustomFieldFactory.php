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

use MauticPlugin\CustomObjectsBundle\Exception\NotFoundException;
use MauticPlugin\CustomObjectsBundle\Provider\CustomFieldTypeProvider;

class CustomFieldFactory
{
    /**
     * @var CustomFieldTypeProvider
     */
    private $customFieldTypeProvider;

    /**
     * @param CustomFieldTypeProvider $customFieldTypeProvider
     */
    public function __construct(CustomFieldTypeProvider $customFieldTypeProvider)
    {
        $this->customFieldTypeProvider = $customFieldTypeProvider;
    }

    /**
     * @param string $type
     *
     * @return CustomField
     */
    public function create(string $type): CustomField
    {
        $customField = new CustomField();

        try {
            $type = $this->customFieldTypeProvider->getType($type);
        } catch (NotFoundException $e) {
            throw new \InvalidArgumentException(
                sprintf("Undefined custom field type '%s'", $type)
            );
        }

        $customField->setType($type);

        return $customField;
    }
}