<?php

declare(strict_types=1);

namespace MauticPlugin\CustomObjectsBundle\CustomFieldType;

class HiddenType extends AbstractTextType
{
    /**
     * @var string
     */
    public const NAME = 'custom.field.type.hidden';

    /**
     * @var string
     */
    protected $key = 'hidden';

    public function getSymfonyFormFieldType(): string
    {
        return \Symfony\Component\Form\Extension\Core\Type\HiddenType::class;
    }
}
