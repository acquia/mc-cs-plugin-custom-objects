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
use Symfony\Component\Validator\Mapping\ClassMetadata;
use MauticPlugin\CustomObjectsBundle\Entity\CustomItemValue;
use MauticPlugin\CustomObjectsBundle\Entity\CustomFieldValueStandard;

class CustomFieldValueInt extends CustomFieldValueStandard
{
    /**
     * @var int|null
     */
    private $value;

    /**
     * @param CustomField $customField
     * @param CustomItem  $customItem
     * @param int|null    $value
     */
    public function __construct(CustomField $customField, CustomItem $customItem, ?int $value = null)
    {
        parent::__construct($customField, $customItem);

        $this->value = $value;
    }

    /**
     * @param ORM\ClassMetadata $metadata
     */
    public static function loadMetadata(ORM\ClassMetadata $metadata)
    {
        $builder = new ClassMetadataBuilder($metadata);
        $builder->setTable('custom_field_value_int');
        $builder->addIndex(['value'], 'value_index');
        $builder->addNullableField('value', Type::INTEGER);
        
        parent::addReferenceColumns($builder);
    }

    /**
     * @param int|null $value
     */
    public function setValue($value = null)
    {
        $this->value = $value;
    }

    /**
     * @return int|null
     */
    public function getValue()
    {
        return $this->value;
    }
}
