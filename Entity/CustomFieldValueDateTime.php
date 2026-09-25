<?php

declare(strict_types=1);

namespace MauticPlugin\CustomObjectsBundle\Entity;

use DateTimeInterface;
use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\Mapping as ORM;
use Mautic\CoreBundle\Doctrine\Mapping\ClassMetadataBuilder;

class CustomFieldValueDateTime extends AbstractCustomFieldValue
{
    /**
     * @var DateTimeInterface|null
     */
    private $value;

    public function __construct(CustomField $customField, CustomItem $customItem, ?DateTimeInterface $value = null)
    {
        parent::__construct($customField, $customItem);

        $this->value = $value;
    }

    public static function loadMetadata(ORM\ClassMetadata $metadata): void
    {
        $builder = new ClassMetadataBuilder($metadata);
        $builder->setTable('custom_field_value_datetime');
        $builder->addIndex(['value'], 'value_index');
        $builder->addNullableField('value', Type::DATETIME);

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
            $value = new \DateTimeImmutable($value, new \DateTimeZone('UTC'));
        }

        $timezone = new \DateTimeZone(date_default_timezone_get());

        if ($value instanceof \DateTime) {
            $value->setTimezone($timezone);
        } elseif ($value instanceof \DateTimeImmutable) {
            $value = $value->setTimezone($timezone);
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
