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
use MauticPlugin\CustomObjectsBundle\Entity\CustomFieldValueInterface;
use MauticPlugin\CustomObjectsBundle\Entity\CustomFieldValueText;
use MauticPlugin\CustomObjectsBundle\Entity\CustomItem;

class TextareaType extends AbstractTextType
{
    protected $key = 'textarea';

    /**
     * @return string
     */
    public function getSymfonyFormFiledType(): string
    {
        return \Symfony\Component\Form\Extension\Core\Type\TextareaType::class;
    }

    /**
     * @return string
     */
    public function getTableAlias(): string
    {
        return 'cfvtextarea';
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
        return new CustomFieldValueText($customField, $customItem, (string) $value);
    }
}