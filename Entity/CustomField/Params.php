<?php

declare(strict_types=1);

namespace MauticPlugin\CustomObjectsBundle\Entity\CustomField;

/**
 * Value object handling `custom_field.params` column content stored in JSON.
 */
class Params
{
    /**
     * @var string|null
     */
    private $placeholder;

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
     * Used as data source for json serialization.
     *
     * @return mixed[]
     */
    public function __toArray(): array
    {
        $return = [
            'placeholder' => $this->placeholder,
        ];

        // Remove null and false values as they are default
        return array_filter($return);
    }

    public function getPlaceholder(): ?string
    {
        return $this->placeholder;
    }

    public function setPlaceholder(?string $placeholder): void
    {
        $this->placeholder = $placeholder;
    }
}
