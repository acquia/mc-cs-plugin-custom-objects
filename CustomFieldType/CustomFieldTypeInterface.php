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
use Symfony\Component\Translation\TranslatorInterface;

interface CustomFieldTypeInterface
{
    public const TABLE_NAME = 'undefined';

    /**
     * @return string
     */
    public function getName(): string;

    /**
     * @return string
     */
    public function getKey(): string;

    /**
     * Symfony form field representing this custom field.
     *
     * @return string
     */
    public function getSymfonyFormFieldType(): string;

    /**
     * Has this field multiple choicesS?
     *
     * @return bool
     */
    public function hasChoices(): bool;

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
     * @return mixed[]
     */
    public function getOperators(): array;

    /**
     * @param TranslatorInterface $translator
     *
     * @return mixed[]
     */
    public function getOperatorOptions(TranslatorInterface $translator): array;

    /**
     * @param mixed[] $options
     *
     * @return mixed[]
     */
    public function createFormTypeOptions(array $options = []): array;
}
