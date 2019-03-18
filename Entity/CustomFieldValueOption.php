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

use Doctrine\ORM\Mapping as ORM;
use Mautic\CoreBundle\Doctrine\Mapping\ClassMetadataBuilder;
use Doctrine\DBAL\Types\Type;

/**
 * Table for multiselect/checkbox option values.
 */
class CustomFieldValueOption extends CustomFieldValueStandard
{
    /**
     * @var string|null
     */
    private $value;

    /**
     * @param CustomField          $customField
     * @param CustomItem           $customItem
     * @param string|string[]|null $value
     */
    public function __construct(CustomField $customField, CustomItem $customItem, $value = null)
    {
        parent::__construct($customField, $customItem);

        $this->value = $value;
    }

    /**
     * @param ORM\ClassMetadata $metadata
     */
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
     * @param string $value
     */
    public function addValue($value): void
    {
        if (!$this->value) {
            $this->value = [];
        }
        
        $this->value[] = $value;
    }

    /**
     * @param mixed $value
     */
    public function setValue($value = null): void
    {
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
