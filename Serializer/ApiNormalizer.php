<?php


namespace MauticPlugin\CustomObjectsBundle\Serializer;


use ApiPlatform\Core\Api\IriConverterInterface;
use MauticPlugin\CustomObjectsBundle\Entity\CustomField;
use MauticPlugin\CustomObjectsBundle\Entity\CustomFieldOption;
use MauticPlugin\CustomObjectsBundle\Entity\CustomItem;
use MauticPlugin\CustomObjectsBundle\Exception\InvalidArgumentException;
use MauticPlugin\CustomObjectsBundle\Exception\NotFoundException;
use MauticPlugin\CustomObjectsBundle\Model\CustomItemModel;
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

    /**
     * @var CustomItemModel
     */
    private $customItemModel;

    /**
     * @var NormalizerInterface
     */
    private $decorated;

    /**
     * @var IriConverterInterface
     */
    private $iriConverter;

    public function __construct(NormalizerInterface $decorated, CustomFieldTypeProvider $customFieldTypeProvider, CustomItemModel $customItemModel, IriConverterInterface $iriConverter)
    {
        if (!$decorated instanceof DenormalizerInterface) {
            throw new \InvalidArgumentException(sprintf('The decorated normalizer must implement the %s.', DenormalizerInterface::class));
        }

        $this->decorated               = $decorated;
        $this->customFieldTypeProvider = $customFieldTypeProvider;
        $this->customItemModel         = $customItemModel;
        $this->iriConverter            = $iriConverter;
    }

    public function supportsNormalization($data, $format = null)
    {
        return $this->decorated->supportsNormalization($data, $format);
    }

    public function normalize($object, $format = null, array $context = [])
    {
        if ($object instanceof CustomItem) {
            return $this->normalizeCustomItem($object, $format, $context);
        }
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
        if ($class === CustomItem::class) {
            return $this->denormalizeCustomItem($data, $class, $format, $context);
        }

        if ($class === CustomField::class) {
            return $this->denormalizeCustomField($data, $class, $format, $context);
        }

        return $this->decorated->denormalize($data, $class, $format, $context);
    }

    public function setSerializer(SerializerInterface $serializer)
    {
        if ($this->decorated instanceof SerializerAwareInterface) {
            $this->decorated->setSerializer($serializer);
        }
    }

    private function normalizeCustomItem($object, $format = null, array $context = [])
    {
        $objectCustomItem = $this->customItemModel->fetchEntity($object->getId());
        // Get obejct from model
        $normalizedObject = $this->decorated->normalize($objectCustomItem, $format, $context);
        // Change id to IRI
        if (array_key_exists('fieldValues', $normalizedObject)) {
            foreach ($normalizedObject['fieldValues'] as &$values) {
                $values['id'] = $this->iriConverter->getItemIriFromResourceClass(CustomField::class, [intval($values['id'])]);
            }
        }
        return $normalizedObject;
    }

    /**
     * @throws ExceptionInterface
     */
    private function denormalizeCustomItem($data, $class, $format = null, array $context = [])
    {
        if (array_key_exists('fieldValues', $data)) {
            foreach ($data['fieldValues'] as &$values) {
                $values['id'] = $this->iriConverter->getItemFromIri($values['id'])->getId();
            }
        }
        return $this->decorated->denormalize($data, $class, $format, $context);
    }

    /**
     * @throws ExceptionInterface
     * @throws InvalidArgumentException
     */
    private function denormalizeCustomField($data, $class, $format = null, array $context = [])
    {
        $optionEntitiesCollection = null;
        $defaultValue = null;
        // Store and unset values that need TypeObject
        if (array_key_exists('options', $data) and count($data['options']) > 0) {
            $options = $data['options'];
            unset($data['options']);
            $optionEntities = [];
            foreach ($options as $option) {
                $optionEntities[] = $this->decorated->denormalize($option, CustomFieldOption::class, $format, $context);
            }
            $optionEntitiesCollection = new ArrayCollection($optionEntities);
        }
        if (array_key_exists('defaultValue', $data)) {
            $defaultValue = $data['defaultValue'];
            unset($data['defaultValue']);
        }

        $entity = $this->decorated->denormalize($data, $class, $format, $context);

        // Set back the stored values when TypeObject is present
        try {
            if (array_key_exists('type', $data)) {
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
        } catch(NotFoundException $e) {
            throw new InvalidArgumentException($e->getMessage());
        }
        return $entity;
    }
}