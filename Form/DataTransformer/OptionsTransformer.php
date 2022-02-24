<?php

declare(strict_types=1);

namespace MauticPlugin\CustomObjectsBundle\Form\DataTransformer;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\PersistentCollection;
use MauticPlugin\CustomObjectsBundle\Entity\CustomFieldOption;
use Symfony\Component\Form\DataTransformerInterface;

class OptionsTransformer implements DataTransformerInterface
{
    /**
     * @param PersistentCollection|CustomFieldOption[] $value
     *
     * @return string[]
     */
    public function transform($value): array
    {
        if (!$value || !$value->count()) {
            return ['list' => []];
        }

        $optionList = [];

        foreach ($value as $option) {
            $optionList[] = $option;
        }

        return [
            'list' => $optionList,
        ];
    }

    /**
     * @param string[] $value
     */
    public function reverseTransform($value): ArrayCollection
    {
        $values  = [];
        $options = [];

        /** @var CustomFieldOption|array $option */
        foreach ($value['list'] as $key => $option) {
            if (is_array($option)) {
                // Newly created option
                $option = new CustomFieldOption($option);
                $option->setOrder($key);
            }

            if (!$option->getLabel() || !$option->getValue()) {
                // Remove incomplete options (missing label or value) represented as array, not CustomFieldOption
                unset($value['list'][$key]);

                continue;
            }

            if (in_array($option->getValue(), $values, false)) {
                // Remove options with the same value as invalid
                unset($value['list'][$key]);

                continue;
            }

            $values[]  = $option->getValue();
            $options[] = $option;
        }

        return new ArrayCollection($options);
    }
}
