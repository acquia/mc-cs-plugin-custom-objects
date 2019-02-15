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

namespace MauticPlugin\CustomObjectsBundle\CustomFieldType;

use MauticPlugin\CustomObjectsBundle\Entity\CustomField;
use MauticPlugin\CustomObjectsBundle\Entity\CustomFieldValueDateTime;
use MauticPlugin\CustomObjectsBundle\Entity\CustomFieldValueInterface;
use MauticPlugin\CustomObjectsBundle\Entity\CustomItem;
use Symfony\Component\Form\FormBuilderInterface;


class DateTimeType extends AbstractCustomFieldType
{
    /**
     * @var string
     */
    protected $key = 'datetime';

    /**
     * @param CustomField $customField
     * @param CustomItem  $customItem
     * @param mixed|null  $value
     *
     * @return CustomFieldValueInterface
     */
    public function createValueEntity(CustomField $customField, CustomItem $customItem, $value = null): CustomFieldValueInterface
    {
        return new CustomFieldValueDateTime($customField, $customItem, new \DateTime($value));
    }

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
            \Symfony\Component\Form\Extension\Core\Type\DateTimeType::class
        )->get($name);
    }

    /**
     * @return string
     */
    public function getEntityClass(): string
    {
        return CustomFieldValueDateTime::class;
    }

    /**
     * @return string
     */
    public function getTableName(): string
    {
        return MAUTIC_TABLE_PREFIX.'custom_field_value_datetime';
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