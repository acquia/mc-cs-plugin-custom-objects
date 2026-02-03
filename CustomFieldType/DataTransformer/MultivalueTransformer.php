<?php

declare(strict_types=1);

namespace MauticPlugin\CustomObjectsBundle\CustomFieldType\DataTransformer;

use Symfony\Component\Form\DataTransformerInterface;

class MultivalueTransformer implements DataTransformerInterface
{
    /**
     * {@inheritdoc}
     */
    public function transform(mixed $value): mixed
    {
        if ($value) {
            return json_decode($value);
        }

        return [];
    }

    /**
     * {@inheritdoc}
     */
    public function reverseTransform(mixed $value): mixed
    {
        if ($value) {
            return json_encode($value);
        }

        return null;
    }
}
