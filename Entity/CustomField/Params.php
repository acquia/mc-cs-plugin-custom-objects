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

use Doctrine\Common\Collections\ArrayCollection;

/**
 * Stored in `custom_field.params` column
 */
class Params
{
    /**
     * @var string|null
     */
    private $requiredValidationMessage;

    /**
     * @var string|null
     */
    private $emptyValue;

    /**
     * @var bool
     */
    private $allowMultiple = false;

    /**
     * @todo Try to use factory if used only in \MauticPlugin\CustomObjectsBundle\EventListener\CustomFieldPostLoadSubscriber
     * @param mixed[] $params
     */
    public function __construct(array $params = [])
    {
        if (array_key_exists('options', $params)) {
            $options = $params['options'];
            unset($params['options']);
        }

        foreach ($params as $key => $value) {
            $this->{$key} = $value;
        }

        $this->options = new ArrayCollection();

        if (isset($options)) {
            foreach ($options as $key => $option) {
                $this->addOption(new Option($option));
            }
        }
    }

    /**
     * Used as data source for json serialization.
     *
     * @return string[]
     */
    public function __toArray(): array
    {
        $return = [
            'requiredValidationMessage' => $this->requiredValidationMessage,
            'emptyValue' => $this->emptyValue,
            'allowMultiple' => $this->allowMultiple,
        ];

        // Remove null and false values as they are default
        return array_filter($return);
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

    /**
     * @return string|null
     */
    public function getEmptyValue(): ?string
    {
        return $this->emptyValue;
    }

    /**
     * @param string|null $emptyValue
     */
    public function setEmptyValue(?string $emptyValue): void
    {
        $this->emptyValue = $emptyValue;
    }

    /**
     * @return bool
     */
    public function isAllowMultiple(): bool
    {
        return $this->allowMultiple;
    }

    /**
     * @param bool $allowMultiple
     */
    public function setAllowMultiple(bool $allowMultiple): void
    {
        $this->allowMultiple = $allowMultiple;
    }
}
