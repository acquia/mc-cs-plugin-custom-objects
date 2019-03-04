<?php

declare(strict_types=1);

/*
* @copyright   2019 Mautic, Inc. All rights reserved
* @author      Mautic, Inc.
*
* @link        https://mautic.com
*
* @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
*/

namespace MauticPlugin\CustomObjectsBundle\Entity\CustomField;

class Params
{
    /**
     * @var string|null
     */
    private $requiredValidationMessage;

    /**
     * @param mixed[] $params
     */
    public function __construct(array $params = [])
    {
        foreach ($params as $key => $value) {
            $this->{$key} = $value;
        }
    }

    /**
     * @return string|null
     */
    public function getRequiredValidationMessage(): ?string
    {
        return $this->requiredValidationMessage;
    }

    /**
     * @param string|null $requiredValidationMessage
     */
    public function setRequiredValidationMessage(?string $requiredValidationMessage): void
    {
        $this->requiredValidationMessage = $requiredValidationMessage;
    }
}
