<?php

declare(strict_types=1);

namespace MauticPlugin\CustomObjectsBundle\CustomFieldType\DataTransformer;

use DateTime;
use Symfony\Component\Form\DataTransformerInterface;

class DateTimeTransformer implements DataTransformerInterface
{
    /**
     * {@inheritdoc}
     */
    public function transform(mixed $value): mixed
    {
        if ($value) {
            return new DateTime($value);
        }

        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function reverseTransform(mixed $value): mixed
    {
        if (!$value) {
            return null;
        }

        if (is_string($value)) {
            return $value;
        }

        return $value->format('Y-m-d H:i');
    }
}
