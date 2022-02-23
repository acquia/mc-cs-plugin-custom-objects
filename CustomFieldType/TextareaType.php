<?php

declare(strict_types=1);

namespace MauticPlugin\CustomObjectsBundle\CustomFieldType;

class TextareaType extends AbstractTextType
{
    /**
     * @var string
     */
    public const NAME = 'custom.field.type.textarea';

    /**
     * @var string
     */
    protected $key = 'textarea';

    public function getSymfonyFormFieldType(): string
    {
        return \Symfony\Component\Form\Extension\Core\Type\TextareaType::class;
    }
}
