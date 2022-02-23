<?php

declare(strict_types=1);

namespace MauticPlugin\CustomObjectsBundle\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Mautic\CoreBundle\Doctrine\Mapping\ClassMetadataBuilder;

/**
 * Table for multiselect/checkbox option values.
 */
class CustomFieldValueOption extends AbstractCustomFieldValue
{
    /**
     * @var int|null
     */
    private $id;

    /**
     * @var string[]|string|null
     */
    private $value;

    /**
     * @param string|string[]|null $value
     */
    public function __construct(CustomField $customField, CustomItem $customItem, $value = null)
    {
        parent::__construct($customField, $customItem);

        $this->setValue($value);
    }

    public static function loadMetadata(ORM\ClassMetadata $metadata): void
    {
        $builder = new ClassMetadataBuilder($metadata);
        $builder->setTable('custom_field_value_option')
            ->addUniqueConstraint(['value', 'custom_field_id', 'custom_item_id'], 'unique')
            ->addFulltextIndex(['value'], 'value_fulltext');

        $builder->addBigIntIdField();

        $builder->createManyToOne('customField', CustomField::class)
            ->addJoinColumn('custom_field_id', 'id', false, false, 'CASCADE')
            ->fetchExtraLazy()
            ->build();

        $builder->createManyToOne('customItem', CustomItem::class)
            ->addJoinColumn('custom_item_id', 'id', false, false, 'CASCADE')
            ->fetchExtraLazy()
            ->build();

        $builder->createField('value', Types::STRING)
            ->build();
    }

    /**
     * @param mixed $value
     */
    public function addValue($value = null)
    {
        if (!$this->value) {
            $this->value = [];
        }

        if (in_array($value, $this->value, true)) {
            return;
        }

        $this->value[] = $value;
    }

    /**
     * @param mixed $value
     */
    public function setValue($value = null)
    {
        if (is_array($value)) {
            $value = array_unique($value);
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
