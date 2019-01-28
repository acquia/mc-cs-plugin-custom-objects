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

namespace MauticPlugin\CustomObjectsBundle\CustomFieldType;

use MauticPlugin\CustomObjectsBundle\Entity\CustomFieldValueInterface;
use MauticPlugin\CustomObjectsBundle\Entity\CustomFieldValueText;
use MauticPlugin\CustomObjectsBundle\Entity\CustomField;
use MauticPlugin\CustomObjectsBundle\Entity\CustomItem;

class TextType extends AbstractCustomFieldType
{
    const KEY = 'text';

    /**
     * @var string
     */
    private $name;

    /**
     * @param string $name field type name translated to user's language
     */
    public function __construct(string $name) 
    {
        $this->name = $name;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return string
     */
    public function getKey(): string
    {
        return self::KEY;
    }

    /**
     * @return string
     */
    public function getSymfonyFormFiledType(): string
    {
        return \Symfony\Component\Form\Extension\Core\Type\TextType::class;
    }

    /**
     * @return string
     */
    public function getEntityClass(): string
    {
        return \MauticPlugin\CustomObjectsBundle\Entity\CustomFieldValueText::class;
    }

    /**
     * @return string
     */
    public function getTableName(): string
    {
        return MAUTIC_TABLE_PREFIX.'custom_field_value_text';
    }

    /**
     * @return string
     */
    public function getTableAlias(): string
    {
        return 'cfvtext';
    }

    /**
     * @param CustomField $customField
     * @param CustomItem  $customItem
     * @param string|null $value
     * 
     * @return CustomFieldValueInterface
     */
    public function createValueEntity(CustomField $customField, CustomItem $customItem, $value = null): CustomFieldValueInterface
    {
        return new CustomFieldValueText($customField, $customItem, $value);
    }
}
