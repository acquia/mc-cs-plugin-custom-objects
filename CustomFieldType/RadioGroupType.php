<?php

declare(strict_types=1);

namespace MauticPlugin\CustomObjectsBundle\CustomFieldType;

class RadioGroupType extends SelectType
{
    /**
     * @var string
     */
    public const NAME = 'custom.field.type.radio_group';

    /**
     * @var string
     */
    protected $key = 'radio_group';

    /**
     * {@inheritdoc}
     */
    protected $formTypeOptions = [
        'expanded'    => true,
        'multiple'    => false,
    ];

    /**
     * {@inheritdoc}
     */
    public function usePlaceholder(): bool
    {
        return false;
    }
}
