<?php

declare(strict_types=1);

namespace MauticPlugin\CustomObjectsBundle\Entity;

interface CustomFieldValueInterface extends UniqueEntityInterface
{
    /**
     * @return mixed
     */
    public function getId();

    /**
     * @return CustomField
     */
    public function getCustomField();

    /**
     * @return CustomItem
     */
    public function getCustomItem();

    public function setCustomItem(CustomItem $customItem): void;

    /**
     * @return mixed
     */
    public function getValue();

    /**
     * @param mixed $value
     */
    public function addValue($value = null);

    /**
     * @param mixed $value
     */
    public function setValue($value = null);
}
