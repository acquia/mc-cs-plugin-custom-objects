<?php

declare(strict_types=1);

namespace MauticPlugin\CustomObjectsBundle\CustomFieldType;

class MultiselectType extends AbstractMultivalueType
{
    /**
     * @var string
     */
    public const NAME = 'custom.field.type.multiselect';

    /**
     * @var string
     */
    protected $key = 'multiselect';

    /**
     * {@inheritdoc}
     */
    protected $formTypeOptions = [
        'expanded' => false,
        'multiple' => true,
    ];

    /**
     * {@inheritdoc}
     */
    public function usePlaceholder(): bool
    {
        return true;
    }
}
