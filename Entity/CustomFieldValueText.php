<?php

declare(strict_types=1);

/*
 * @copyright   2018 Mautic, Inc. All rights reserved
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

class CustomFieldValueText extends CustomFieldValueStandard
{
    /**
     * @var string|null
     */
    private $value;

    /**
     * @param CustomField $customField
     * @param CustomItem  $customItem
     * @param string|null $value
     */
    public function __construct(CustomField $customField, CustomItem $customItem, ?string $value = null)
    {
        parent::__construct($customField, $customItem);

        $this->value = $value;
    }

    /**
     * Doctrine doesn't support prefix indexes. It's being added in the updatePluginSchema method.
     * $builder->addIndex(['value(64)'], 'value_index');
     *
     * @param ORM\ClassMetadata $metadata
     */
    public static function loadMetadata(ORM\ClassMetadata $metadata): void
    {
        $builder = new ClassMetadataBuilder($metadata);
        $builder->setTable('custom_field_value_text');
        $builder->addNullableField('value', Type::TEXT);

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
