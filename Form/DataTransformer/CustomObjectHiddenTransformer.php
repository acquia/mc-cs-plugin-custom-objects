<?php

declare(strict_types=1);

namespace MauticPlugin\CustomObjectsBundle\Form\DataTransformer;

use MauticPlugin\CustomObjectsBundle\Entity\CustomObject;
use MauticPlugin\CustomObjectsBundle\Repository\CustomObjectRepository;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Exception\TransformationFailedException;

class CustomObjectHiddenTransformer implements DataTransformerInterface
{
    /**
     * @var CustomObjectRepository
     */
    private $customObjectRepository;

    public function __construct(CustomObjectRepository $customObjectRepository)
    {
        $this->customObjectRepository = $customObjectRepository;
    }

    /**
     * {@inheritdoc}
     */
    public function transform($value)
    {
        if (!$value) {
            return '';
        }

        return $value->getId();
    }

    /**
     * {@inheritdoc}
     */
    public function reverseTransform($value)
    {
        if (!$value) {
            return new CustomObject();
        }

        $entity = $this->customObjectRepository->findOneBy(['id' => $value]);

        if (null === $entity) {
            throw new TransformationFailedException(sprintf('An entity with ID "%s" does not exist!', $value));
        }

        return $entity;
    }
}
