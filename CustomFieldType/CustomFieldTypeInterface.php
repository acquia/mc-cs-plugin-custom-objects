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

use MauticPlugin\CustomObjectsBundle\Entity\CustomField;
use MauticPlugin\CustomObjectsBundle\Entity\CustomItem;
use MauticPlugin\CustomObjectsBundle\Entity\CustomFieldValueInterface;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Translation\TranslatorInterface;

interface CustomFieldTypeInterface
{
    /**
     * @return string
     */
    public function getName(): string;

    /**
     * @return string
     */
    public function getKey(): string;

    /**
     * @param FormBuilderInterface $builder
     * @param string               $name
     *
     * @return FormBuilderInterface
     */
    public function createSymfonyFormFiledType(FormBuilderInterface $builder, string $name): FormBuilderInterface;

    /**
     * @return string
     */
    public function getEntityClass(): string;

    /**
     * @param CustomField $customField
     * @param CustomItem  $customItem
     * @param mixed|null  $value
     * 
     * @return CustomFieldValueInterface
     */
    public function createValueEntity(CustomField $customField, CustomItem $customItem, $value = null): CustomFieldValueInterface;

    /**
     * @return string
     */
    public function getTableName(): string;

    /**
     * @return string
     */
    public function getTableAlias(): string;

    /**
     * @return array
     */
    public function getOperators(): array;

    /**
     * @param TranslatorInterface $translator
     * 
     * @return array
     */
    public function getOperatorOptions(TranslatorInterface $translator): array;
}
