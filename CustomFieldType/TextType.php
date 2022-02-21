<?php

declare(strict_types=1);

namespace MauticPlugin\CustomObjectsBundle\CustomFieldType;

class TextType extends AbstractTextType
{
    /**
     * @var string
     */
    public const NAME = 'custom.field.type.text';

    /**
     * @var string
     */
    protected $key = 'text';
}
