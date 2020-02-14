<?php

declare(strict_types=1);

/*
 * @copyright   2019 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc
 *
 * @link        https://mautic.com
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

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
