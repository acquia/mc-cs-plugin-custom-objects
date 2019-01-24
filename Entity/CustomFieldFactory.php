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
     * @param string       $type
     * @param CustomObject $customObject
     *
     * @return CustomField
     * @throws NotFoundException
     */
    public function create(string $type, CustomObject $customObject): CustomField
    {
        $typeObject = $this->customFieldTypeProvider->getType($type);

        $customField = new CustomField();

        $customField->setType($type);
        $customField->setTypeObject($typeObject);
        $customField->setCustomObject($customObject);

        return $customField;
    }
}