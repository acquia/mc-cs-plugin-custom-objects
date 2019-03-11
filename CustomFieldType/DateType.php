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
use MauticPlugin\CustomObjectsBundle\Entity\CustomFieldValueDate;
use MauticPlugin\CustomObjectsBundle\Entity\CustomFieldValueDateTime;
use MauticPlugin\CustomObjectsBundle\Entity\CustomFieldValueInterface;
use MauticPlugin\CustomObjectsBundle\Entity\CustomItem;

class DateType extends AbstractCustomFieldType
{
    public const TABLE_NAME = 'custom_field_value_date';

    /**
     * @var string
     */
    protected $key = 'date';

    /**
     * @var array
     */
    protected $formTypeOptions = [
        'widget'     => 'single_text',
        'attr'       => [
            'data-toggle' => 'date',
        ],
        'format'   => 'yyyy-MM-dd',
    ];

    /**
     * @param CustomField $customField
     * @param CustomItem  $customItem
     * @param mixed|null  $value
     *
     * @return CustomFieldValueInterface
     */
    public function createValueEntity(CustomField $customField, CustomItem $customItem, $value = null): CustomFieldValueInterface
    {
        if (null !== $value) {
            $value = new \DateTimeImmutable($value);
        }

        return new CustomFieldValueDateTime($customField, $customItem, $value);
    }

    /**
     * @return string
     */
    public function getSymfonyFormFieldType(): string
    {
        return \Symfony\Component\Form\Extension\Core\Type\DateType::class;
    }

    /**
     * @return string
     */
    public function getEntityClass(): string
    {
        return CustomFieldValueDate::class;
    }
}
