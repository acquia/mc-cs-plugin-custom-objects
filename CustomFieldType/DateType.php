<?php

/*
 * @copyright   2019 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\CustomObjectsBundle\CustomFieldType;

use MauticPlugin\CustomObjectsBundle\Entity\CustomField;
use MauticPlugin\CustomObjectsBundle\Entity\CustomFieldValueDateTime;
use MauticPlugin\CustomObjectsBundle\Entity\CustomFieldValueInterface;
use MauticPlugin\CustomObjectsBundle\Entity\CustomItem;

class DateType extends AbstractCustomFieldType
{
    protected $key = 'date';

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
     * @return string
     */
    public function getSymfonyFormFiledType(): string
    {
        return \Symfony\Component\Form\Extension\Core\Type\DateType::class;
    }

    /**
     * @return string
     */
    public function getEntityClass(): string
    {
        // TODO: Implement getEntityClass() method.
    }

    /**
     * @return string
     */
    public function getTableName(): string
    {
        // TODO: Implement getTableName() method.
    }

    /**
     * @return string
     */
    public function getTableAlias(): string
    {
        // TODO: Implement getTableAlias() method.
    }
}