<?php

declare(strict_types=1);

namespace MauticPlugin\CustomObjectsBundle\Form\DataTransformer;

use Doctrine\Common\Collections\ArrayCollection;
use JMS\Serializer\SerializerInterface;
use MauticPlugin\CustomObjectsBundle\Entity\CustomField;
use MauticPlugin\CustomObjectsBundle\Entity\CustomFieldOption;
use MauticPlugin\CustomObjectsBundle\Exception\NotFoundException;
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
     * @var CustomField[];
     */
    private $customFieldCache = [];

    public function __construct(SerializerInterface $serializer, CustomFieldModel $customFieldModel)
    {
        $this->serializer       = $serializer;
        $this->customFieldModel = $customFieldModel;
    }

    /**
     * Transforms a collection of objects (CustomFieldOption[]) to a string (json).
     *
     * @param ArrayCollection|CustomFieldOption[]|null $options
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
            if (array_key_exists('customField', $option)) {
                // It does not exists in newly created custom fields
                $option['customField'] = $this->fetchCustomFieldById($option['customField']);
            }
            $options[$key]         = new CustomFieldOption($option);
        }

        return new ArrayCollection($options);
    }

    /**
     * @throws NotFoundException
     */
    private function fetchCustomFieldById(int $id): CustomField
    {
        if (!array_key_exists($id, $this->customFieldCache)) {
            $this->customFieldCache[$id] = $this->customFieldModel->fetchEntity($id);
        }

        return $this->customFieldCache[$id];
    }
}
