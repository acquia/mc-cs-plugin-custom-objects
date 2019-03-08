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

namespace MauticPlugin\CustomObjectsBundle\Form\DataTransformer;

use JMS\Serializer\Serializer;
use MauticPlugin\CustomObjectsBundle\Entity\CustomField\Params;
use Symfony\Component\Form\DataTransformerInterface;

class ParamsToStringTransformer implements DataTransformerInterface
{
    /**
     * @var Serializer
     */
    private $serializer;

    /**
     * @param Serializer $serializer
     */
    public function __construct(Serializer $serializer)
    {
        $this->serializer = $serializer;
    }

    /**
     * Transforms an object (Params) to a string (json).
     *
     * @param Params $params
     *
     * @return string
     */
    public function transform($params): string
    {
        return $this->serializer->serialize($params->__toArray(), 'json');
    }

    /**
     * Transforms a string (json) to an object (Params).
     *
     * @param string $params
     *
     * @return Params
     */
    public function reverseTransform($params): Params
    {
        $params = json_decode($params, true);

        return new Params($params);
    }
}
