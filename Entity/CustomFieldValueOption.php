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

/**
 * Table for multiselect/checkbox option values.
 */
class CustomFieldValueOption extends CustomFieldValueStandard
{
    /**
     * @var CustomFieldOption|null
     */
    private $option;

    /**
     * @param CustomField       $customField
     * @param CustomItem        $customItem
     * @param CustomFieldOption $option
     */
    public function __construct(CustomField $customField, CustomItem $customItem, CustomFieldOption $option)
    {
        parent::__construct($customField, $customItem);

        $this->option = $option;
    }

    /**
     * @param ORM\ClassMetadata $metadata
     */
    public static function loadMetadata(ORM\ClassMetadata $metadata): void
    {
        $builder = new ClassMetadataBuilder($metadata);
        $builder->setTable('custom_field_value_option');

        parent::addReferenceColumns($builder);

        $builder->createManyToOne('option', CustomFieldOption::class)
            ->addJoinColumn('option_id', 'id', false, false, 'CASCADE')
            ->makePrimaryKey()
            ->fetchExtraLazy()
            ->build();
    }

    /**
     * @param CustomFieldOption $option
     */
    public function setOption($option = null): void
    {
        $this->option = $option;
    }

    /**
     * @return CustomFieldOption|null
     */
    public function getOption()
    {
        return $this->option;
    }

    /**
     * @param mixed $value
     */
    public function setValue($value = null): void
    {
        $this->setOption($value);
    }

    /**
     * @return mixed
     */
    public function getValue()
    {
        return $this->getOption();
    }
}
