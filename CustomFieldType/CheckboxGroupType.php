<?php

declare(strict_types=1);

namespace MauticPlugin\CustomObjectsBundle\CustomFieldType;

class CheckboxGroupType extends AbstractMultivalueType
{
    /**
     * @var string
     */
    public const NAME = 'custom.field.type.checkbox_group';

    /**
     * @var string
     */
    protected $key = 'checkbox_group';

    /**
     * {@inheritdoc}
     */
    protected $formTypeOptions = [
        'expanded' => true,
        'multiple' => true,
    ];

    /**
     * {@inheritdoc}
     */
    public function usePlaceholder(): bool
    {
        return false;
    }
}
