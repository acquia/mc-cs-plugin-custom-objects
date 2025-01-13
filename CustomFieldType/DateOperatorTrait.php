<?php

declare(strict_types=1);

namespace MauticPlugin\CustomObjectsBundle\CustomFieldType;

trait DateOperatorTrait
{
    /**
     * Remove operators that are supported only by segment filters.
     *
     * @return string[]
     */
    public function getOperatorOptions(): array
    {
        $options = parent::getOperatorOptions();

        unset($options['between'], $options['!between'], $options['inLast'], $options['inNext']);

        return $options;
    }

    /**
     * @return mixed[]
     */
    public function getOperators(): array
    {
        $allOperators     = parent::getOperators();
        $allowedOperators = array_flip(['=', '!=', 'gt', 'gte', 'lt', 'lte', 'empty', '!empty', 'between', '!between', 'inLast', 'inNext']);

        return array_intersect_key($allOperators, $allowedOperators);
    }

    public function getOperatorsForSegment(): array
    {
        return parent::getOperators();
    }
}
