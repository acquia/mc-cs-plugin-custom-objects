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
use Symfony\Component\Form\FormBuilderInterface;

class IntType extends AbstractCustomFieldType
{
    /**
     * @var string
     */
    protected $key = 'int';

    /**
     * @param FormBuilderInterface $builder
     * @param string               $name
     *
     * @return FormBuilderInterface
     */
    public function createSymfonyFormFiledType(FormBuilderInterface $builder, string $name): FormBuilderInterface
    {
        return $builder->add(
            $name,
            \Symfony\Component\Form\Extension\Core\Type\NumberType::class
        )->get($name);
    }

    /**
     * @return string
     */
    public function getEntityClass(): string
    {
        return CustomFieldValueInt::class;
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
