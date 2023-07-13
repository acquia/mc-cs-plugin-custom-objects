<?php

declare(strict_types=1);

namespace MauticPlugin\CustomObjectsBundle\Entity;

use DateTimeInterface;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Mautic\CoreBundle\Doctrine\Mapping\ClassMetadataBuilder;

class CustomFieldValueDate extends AbstractCustomFieldValue
{
    /**
     * @var DateTimeInterface|null
     */
    private $value;

    public function __construct(CustomField $customField, CustomItem $customItem, ?DateTimeInterface $value = null)
    {
        parent::__construct($customField, $customItem);

        $this->setValue($value);
    }

    public static function loadMetadata(ORM\ClassMetadata $metadata): void
    {
        $builder = new ClassMetadataBuilder($metadata);
        $builder->setTable('custom_field_value_date');
        $builder->addIndex(['value'], 'value_index');
        $builder->addNullableField('value', Types::DATETIME_MUTABLE);

        parent::addReferenceColumns($builder);
    }

    /**
     * @param mixed $value
     */
    public function setValue($value = null): void
    {
        if (empty($value)) {
            $this->value = null;

            return;
        }

        if (!$value instanceof DateTimeInterface) {
            $value = new \DateTimeImmutable($value);
        }

        $this->value = $value;
    }

    /**
     * @return mixed
     */
    public function getValue()
    {
        return $this->value;
    }
}
