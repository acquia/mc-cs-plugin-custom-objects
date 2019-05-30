<?php

declare(strict_types=1);

/*
 * @copyright   2019 Mautic Inc. All rights reserved
 *
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.com
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\CustomObjectsBundle\Exception;

use Exception;
use MauticPlugin\CustomObjectsBundle\Entity\CustomField;

class InvalidValueException extends Exception
{
    /**
     * @var CustomField|null
     */
    private $customField;

    /**
     * @param CustomField $customField
     */
    public function setCustomField(CustomField $customField): void
    {
        $this->customField = $customField;
    }

    /**
     * @return CustomField|null
     */
    public function getCustomField(): ?CustomField
    {
        return $this->customField;
    }
}
