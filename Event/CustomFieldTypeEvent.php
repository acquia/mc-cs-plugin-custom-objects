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

namespace MauticPlugin\CustomObjectsBundle\Event;

use Symfony\Component\EventDispatcher\Event;
use MauticPlugin\CustomObjectsBundle\CustomFieldType\CustomFieldTypeInterface;

class CustomFieldTypeEvent extends Event
{
    /**
     * @var array
     */
    private $customFieldTypes = [];

    /**
     * @param CustomFieldTypeInterface
     */
    public function addCustomFieldType(CustomFieldTypeInterface $customFieldType): void
    {
        $this->customFieldTypes[$customFieldType->getKey()] = $customFieldType;;
    }

    /**
     * @return CustomFieldTypeInterface[]
     */
    public function getCustomFieldTypes(): array
    {
        return $this->customFieldTypes;
    }
}
