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

use MauticPlugin\CustomObjectsBundle\Entity\CustomField\Params;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Exception\TransformationFailedException;

class ParamsToJsonTransformer implements DataTransformerInterface
{
    /**
     * Transforms an object (Params) to a string (json).
     *
     * @param Params $params
     *
     * @return array
     */
    public function transform($params)
    {
        $params = json_encode($params);

        return $params;

    }

    /**
     * Transforms a string (json) to an object (Params).
     *
     * @param  string $params
     * @return Params
     * @throws TransformationFailedException if object (issue) is not found.
     */
    public function reverseTransform($params)
    {
        $params = json_decode($params);

        return $params;
    }
}