<?php

/*
 * @copyright   2019 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\CustomObjectsBundle\Form;

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

    /**
     * @param CustomObjectRepository $customObjectRepository
     */
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

        $entity = $this->customObjectRepository->findOneById($value);

        if (null === $entity) {
            throw new TransformationFailedException(sprintf(
                'An entity with ID "%s" does not exist!',
                $value
            ));
        }

        return $entity;
    }
}