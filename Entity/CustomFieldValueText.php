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
use Symfony\Component\Validator\Mapping\ClassMetadata;
use Mautic\CoreBundle\Entity\FormEntity;
use MauticPlugin\CustomObjectsBundle\Entity\CustomFieldValue;
use Ramsey\Uuid\Uuid;

class CustomFieldValueText extends FormEntity
{
    /**
     * @var string
     */
    private $id;

    /**
     * @var CustomFieldValue
     */
    private $customFieldValue;

    /**
     * @var string
     */
    private $value;

    /**
     * @param CustomFieldValue $customFieldValue
     * @param string           $value
     */
    public function __construct(CustomFieldValue $customFieldValue, string $value)
    {
        $this->id               = Uuid::uuid4()->toString();
        $this->customFieldValue = $customFieldValue;
        $this->value            = $value;
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

        $builder->addUuid();

        $builder->createManyToOne('customFieldValue', CustomFieldValue::class)
            ->addJoinColumn('custom_field_value_id', 'id', false, false, 'CASCADE')
            ->build();

        $builder->addField('value', Type::TEXT);
    }

    /**
     * @return string
     */
    public function getId(): string
    {
        return $this->id;
    }

    /**
     * @param ClassMetadata $metadata
     */
    public static function loadValidatorMetadata(ClassMetadata $metadata): void
    {
        $metadata->addPropertyConstraint('customFieldValue', new Assert\NotBlank());
    }

    /**
     * @return CustomFieldValue
     */
    public function getCustomFieldValue(): CustomFieldValue
    {
        return $this->customFieldValue;
    }

    /**
     * @return string
     */
    public function getCustomValue(): string
    {
        return $this->value;
    }
}
