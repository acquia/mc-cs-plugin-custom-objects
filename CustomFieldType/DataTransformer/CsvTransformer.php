<?php

declare(strict_types=1);

namespace MauticPlugin\CustomObjectsBundle\CustomFieldType\DataTransformer;

use MauticPlugin\CustomObjectsBundle\Helper\CsvHelper;
use Symfony\Component\Form\DataTransformerInterface;

class CsvTransformer implements DataTransformerInterface
{
    /**
     * @var CsvHelper
     */
    private $csvHelper;

    public function __construct()
    {
        $this->csvHelper = new CsvHelper();
    }

    /**
     * {@inheritdoc}
     */
    public function transform($value)
    {
        if (empty($value)) {
            return '';
        }

        if (is_array($value)) {
            return $this->csvHelper->arrayToCsvLine($value);
        }

        return $value;
    }

    /**
     * {@inheritdoc}
     */
    public function reverseTransform($value)
    {
        if (empty($value)) {
            return [];
        }

        if (is_string($value)) {
            return $this->csvHelper->csvLineToArray($value);
        }

        return $value;
    }
}
