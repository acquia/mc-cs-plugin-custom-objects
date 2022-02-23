<?php

declare(strict_types=1);

namespace MauticPlugin\CustomObjectsBundle\Entity;

use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\Mapping as ORM;
use Mautic\CoreBundle\Doctrine\Mapping\ClassMetadataBuilder;

class CustomFieldValueText extends AbstractCustomFieldValue
{
    /**
     * @var string|null
     */
    private $value;

    public function __construct(CustomField $customField, CustomItem $customItem, ?string $value = null)
    {
        parent::__construct($customField, $customItem);

        $this->value = $value;
    }

    /**
     * Doctrine doesn't support prefix indexes. It's being added in the updatePluginSchema method.
     * $builder->addIndex(['value(64)'], 'value_index');.
     */
    public static function loadMetadata(ORM\ClassMetadata $metadata): void
    {
        $builder = new ClassMetadataBuilder($metadata);
        $builder->setTable('custom_field_value_text')
            ->addNullableField('value', Type::TEXT)
            ->addFulltextIndex(['value'], 'value_fulltext');

        parent::addReferenceColumns($builder);
    }

    /**
     * @param mixed $value
     */
    public function setValue($value = null): void
    {
        $this->value = (string) $value;
    }

    /**
     * @return mixed
     */
    public function getValue()
    {
        return $this->value;
    }
}
