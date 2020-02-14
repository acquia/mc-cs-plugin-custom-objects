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

namespace MauticPlugin\CustomObjectsBundle\Entity;

use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\Mapping as ORM;
use Mautic\CoreBundle\Doctrine\Mapping\ClassMetadataBuilder;

/**
 * Table for multiselect/checkbox option values.
 */
class CustomFieldValueOption extends AbstractCustomFieldValue
{
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
        $builder->setTable('custom_field_value_option');

        parent::addReferenceColumns($builder);

        $builder->createField('value', Type::STRING)
            ->makePrimaryKey()
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
