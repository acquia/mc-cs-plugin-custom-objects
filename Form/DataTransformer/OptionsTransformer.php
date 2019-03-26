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

use Symfony\Component\Form\DataTransformerInterface;

class OptionsTransformer implements DataTransformerInterface
{
    /**
     * {@inheritdoc}
     */
    public function transform($value)
    {
        if ($value) {
            throw new \InvalidArgumentException("Not implemented");
        }

        return ['list' => []] ;
    }

    /**
     * {@inheritdoc}
     */
    public function reverseTransform($value)
    {
        return $value;
    }
}