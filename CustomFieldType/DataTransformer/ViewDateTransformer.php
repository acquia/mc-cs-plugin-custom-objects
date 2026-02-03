<?php

declare(strict_types=1);

namespace MauticPlugin\CustomObjectsBundle\CustomFieldType\DataTransformer;

use Symfony\Component\Form\DataTransformerInterface;

class ViewDateTransformer implements DataTransformerInterface
{
    /**
     * {@inheritdoc}
     */
    public function transform(mixed $value): mixed
    {
        if ('' === $value) {
            return null;
        }

        return $value;
    }

    /**
     * {@inheritdoc}
     */
    public function reverseTransform(mixed $value): mixed
    {
        if (null === $value) {
            return '';
        }

        return $value;
    }
}
