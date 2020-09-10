<?php


namespace MauticPlugin\CustomObjectsBundle\Serializer;


use MauticPlugin\CustomObjectsBundle\Entity\CustomField;
use MauticPlugin\CustomObjectsBundle\Entity\CustomFieldOption;
use MauticPlugin\CustomObjectsBundle\Exception\InvalidArgumentException;
use MauticPlugin\CustomObjectsBundle\Exception\NotFoundException;
use MauticPlugin\CustomObjectsBundle\Provider\CustomFieldTypeProvider;
use Symfony\Component\Serializer\Exception\ExceptionInterface;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Serializer\SerializerAwareInterface;
use Symfony\Component\Serializer\SerializerInterface;
use Doctrine\Common\Collections\ArrayCollection;

final class ApiNormalizer implements NormalizerInterface, DenormalizerInterface, SerializerAwareInterface
{
    /**
     * @var CustomFieldTypeProvider
     */
    private $customFieldTypeProvider;

    private $decorated;

    public function __construct(NormalizerInterface $decorated, CustomFieldTypeProvider $customFieldTypeProvider)
    {
        if (!$decorated instanceof DenormalizerInterface) {
            throw new \InvalidArgumentException(sprintf('The decorated normalizer must implement the %s.', DenormalizerInterface::class));
        }

        $this->decorated = $decorated;
        $this->customFieldTypeProvider = $customFieldTypeProvider;
    }

    public function supportsNormalization($data, $format = null)
    {
        return $this->decorated->supportsNormalization($data, $format);
    }

    public function normalize($object, $format = null, array $context = [])
    {
        return $this->decorated->normalize($object, $format, $context);
    }

    public function supportsDenormalization($data, $type, $format = null)
    {
        return $this->decorated->supportsDenormalization($data, $type, $format);
    }

    /**
     * @throws InvalidArgumentException
     * @throws ExceptionInterface
     */
    public function denormalize($data, $class, $format = null, array $context = [])
    {
        $optionEntitiesCollection = null;
        $defaultValue = null;
        if ($class === CustomField::class and array_key_exists('options', $data) and count($data['options']) > 0) {
            // Store and unset values that need TypeObject
            $options = $data['options'];
            unset($data['options']);
            $defaultValue = $data['defaultValue'];
            unset($data['defaultValue']);
            $optionEntities = [];
            foreach($options as $option){
                $optionEntities[] = $this->decorated->denormalize($option, CustomFieldOption::class, $format, $context);
            }
            $optionEntitiesCollection = new ArrayCollection($optionEntities);
        }
        $entity = $this->decorated->denormalize($data, $class, $format, $context);
        if ($entity instanceof CustomField) {
            try {
                if(array_key_exists('type', $data)) {
                    $type = $data['type'];
                    $typeObject = $this->customFieldTypeProvider->getType($type);
                    $entity->setTypeObject($typeObject);
                }
                if ($optionEntitiesCollection) {
                    $entity->setOptions($optionEntitiesCollection);
                }
                if ($defaultValue) {
                    $entity->setDefaultValue($defaultValue);
                }
            }
            catch(NotFoundException $e) {
                throw new InvalidArgumentException($e->getMessage());
            }
        }
        return $entity;
    }

    public function setSerializer(SerializerInterface $serializer)
    {
        if($this->decorated instanceof SerializerAwareInterface) {
            $this->decorated->setSerializer($serializer);
        }
    }
}