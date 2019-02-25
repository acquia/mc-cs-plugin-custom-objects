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

    /**
     * @return mixed
     */
    public function getValue();

    /**
     * @param mixed $value
     */
    public function setValue($value = null);

    public function updateThisEntityManually();

    /**
     * @return bool
     */
    public function shouldBeUpdatedManually();
}
