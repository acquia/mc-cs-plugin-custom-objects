<?php

declare(strict_types=1);

/*
 * @copyright   2019 Mautic, Inc. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.com
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\CustomObjectsBundle\CustomFieldType\DataTransformer;

use Symfony\Component\Form\DataTransformerInterface;

class MultivalueTransformer implements DataTransformerInterface
{
    /**
     * {@inheritdoc}
     */
    public function transform($value)
    {
        if ($value) {
            return json_decode($value);
        }

        return [];
    }

    /**
     * {@inheritdoc}
     */
    public function reverseTransform($value)
    {
        if ($value) {
            return json_encode($value);
        }
    }
}
