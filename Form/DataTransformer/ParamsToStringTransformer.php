<?php

declare(strict_types=1);

namespace MauticPlugin\CustomObjectsBundle\Form\DataTransformer;

use JMS\Serializer\SerializerInterface;
use MauticPlugin\CustomObjectsBundle\Entity\CustomField\Params;
use Symfony\Component\Form\DataTransformerInterface;

class ParamsToStringTransformer implements DataTransformerInterface
{
    /**
     * @var SerializerInterface
     */
    private $serializer;

    public function __construct(SerializerInterface $serializer)
    {
        $this->serializer = $serializer;
    }

    /**
     * Transforms an object (Params) to a string (json).
     *
     * @param Params|null $params
     */
    public function transform($params = null): string
    {
        if (null === $params) {
            // Param can be null because entities are not using constructors
            return '[]';
        }

        if ($params instanceof Params) {
            $params = $params->__toArray();
        }

        return $this->serializer->serialize($params, 'json');
    }

    /**
     * Transforms a string (json) to an object (Params).
     *
     * @param string $params
     */
    public function reverseTransform($params): Params
    {
        $params = json_decode($params, true);

        return new Params($params);
    }
}
