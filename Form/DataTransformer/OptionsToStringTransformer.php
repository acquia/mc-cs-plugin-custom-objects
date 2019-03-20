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

use Doctrine\Common\Collections\ArrayCollection;
use JMS\Serializer\SerializerInterface;
use MauticPlugin\CustomObjectsBundle\Entity\CustomFieldOption;
use MauticPlugin\CustomObjectsBundle\Model\CustomFieldModel;
use Symfony\Component\Form\DataTransformerInterface;

class OptionsToStringTransformer implements DataTransformerInterface
{
    /**
     * @var SerializerInterface
     */
    private $serializer;

    /**
     * @var CustomFieldModel
     */
    private $customFieldModel;

    /**
     * @param SerializerInterface $serializer
     * @param CustomFieldModel    $customFieldModel
     */
    public function __construct(SerializerInterface $serializer, CustomFieldModel $customFieldModel)
    {
        $this->serializer       = $serializer;
        $this->customFieldModel = $customFieldModel;
    }

    /**
     * Transforms a collection of objects (CustomFieldOption[]) to a string (json).
     *
     * @param ArrayCollection|CustomFieldOption[]|null $options
     *
     * @return string
     */
    public function transform($options = null): string
    {
        if (!$options) {
            // Options can be null because entities are not using constructors
            return '[]';
        }

        return $this->serializer->serialize(
            $options->map(function (CustomFieldOption $option) {
                return $option->__toArray();
            })->toArray(),
            'json'
        );
    }

    /**
     * Transforms a string (json) to an objects (CustomFieldOption[]).
     *
     * @param string $options
     *
     * @return ArrayCollection|CustomFieldOption[]
     */
    public function reverseTransform($options): ArrayCollection
    {
        $options = json_decode($options, true);

        foreach ($options as $key => $option) {
            $option['customField'] = $this->customFieldModel->fetchEntity($option['customField']);
            $options[$key]         = new CustomFieldOption($option);
        }

        return new ArrayCollection($options);
    }
}
