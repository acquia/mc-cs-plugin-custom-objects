<?php

declare(strict_types=1);

/*
 * @copyright   2019 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc
 *
 * @link        https://mautic.com
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\CustomObjectsBundle\Entity;

use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\Mapping as ORM;
use Mautic\CoreBundle\Doctrine\Mapping\ClassMetadataBuilder;
use DateTimeInterface;

class CustomFieldValueDate extends CustomFieldValueStandard
{
    /**
     * @var DateTimeInterface|null
     */
    private $value;

    /**
     * @param CustomField            $customField
     * @param CustomItem             $customItem
     * @param DateTimeInterface|null $value
     */
    public function __construct(CustomField $customField, CustomItem $customItem, ?DateTimeInterface $value = null)
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
        $builder->setTable('custom_field_value_date');
        $builder->addIndex(['value'], 'value_index');
        $builder->addNullableField('value', Type::DATE);

        parent::addReferenceColumns($builder);
    }

    /**
     * @param mixed $value
     */
    public function setValue($value = null): void
    {
        if (null !== $value && !$value instanceof DateTimeInterface) {
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
