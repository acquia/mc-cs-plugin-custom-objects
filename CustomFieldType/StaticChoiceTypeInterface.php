<?php

declare(strict_types=1);

namespace MauticPlugin\CustomObjectsBundle\CustomFieldType;

/**
 * Use this for choice fields with static choice list like the country field.
 */
interface StaticChoiceTypeInterface
{
    /**
     * @return string[]
     */
    public function getChoices(): array;
}
