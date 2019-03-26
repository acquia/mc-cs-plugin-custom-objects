<?php

/*
* @copyright   2019 Mautic, Inc. All rights reserved
* @author      Mautic, Inc.
*
* @link        https://mautic.com
*
* @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
*/

namespace MauticPlugin\CustomObjectsBundle\Form\DataTransformer;

use Doctrine\ORM\PersistentCollection;
use MauticPlugin\CustomObjectsBundle\Entity\CustomFieldOption;
use Symfony\Component\Form\DataTransformerInterface;

class OptionsTransformer implements DataTransformerInterface
{
    /**
     * @param PersistentCollection|CustomFieldOption[] $value
     * {@inheritdoc}
     */
    public function transform($value)
    {
        if (!$value || !$value->count()) {
            // @todo empty value
            return ['list' => []];
        }

        $return = [];

        foreach ($value as $option) {
            $return[] = $option;
        }

        return  [
            'list' => $return,
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function reverseTransform($value)
    {
        return $value;
    }
}