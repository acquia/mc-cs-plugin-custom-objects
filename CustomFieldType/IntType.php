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

use MauticPlugin\CustomObjectsBundle\Entity\CustomFieldValueInt;
use MauticPlugin\CustomObjectsBundle\Entity\CustomFieldValueInterface;
use MauticPlugin\CustomObjectsBundle\Entity\CustomField;
use MauticPlugin\CustomObjectsBundle\Entity\CustomItem;

class IntType extends AbstractCustomFieldType
{
    const KEY = 'int';

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
        return \Symfony\Component\Form\Extension\Core\Type\NumberType::class;
    }

    /**
     * @return string
     */
    public function getEntityClass(): string
    {
        return \MauticPlugin\CustomObjectsBundle\Entity\CustomFieldValueInt::class;
    }

    /**
     * @return string
     */
    public function getTableName(): string
    {
        return MAUTIC_TABLE_PREFIX.'custom_field_value_int';
    }

    /**
     * @return string
     */
    public function getTableAlias(): string
    {
        return 'cfvint';
    }

    /**
     * @param CustomField $customField
     * @param CustomItem  $customItem
     * @param int|null    $value
     * 
     * @return CustomFieldValueInterface
     */
    public function createValueEntity(CustomField $customField, CustomItem $customItem, $value = null): CustomFieldValueInterface
    {
        return new CustomFieldValueInt($customField, $customItem, (int) $value);
    }

    /**
     * @return array
     */
    public function getOperators(): array
    {
        $allOperators = parent::getOperators();
        $allowedOperators = array_flip(['=', '!=', 'gt', 'gte', 'lt', 'lte', 'empty', '!empty', 'between', '!between', 'in', '!in']);
        
        return array_intersect_key($allOperators, $allowedOperators);
    }
}
