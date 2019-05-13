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

namespace MauticPlugin\CustomObjectsBundle\Model;

use MauticPlugin\CustomObjectsBundle\Entity\CustomItem;
use Doctrine\ORM\EntityManager;
use Mautic\CoreBundle\Model\FormModel;
use MauticPlugin\CustomObjectsBundle\Entity\CustomObject;
use Mautic\LeadBundle\Entity\Import;
use Mautic\CoreBundle\Templating\Helper\FormatterHelper;
use Mautic\UserBundle\Entity\User;
use MauticPlugin\CustomObjectsBundle\Entity\CustomFieldValueInterface;
use MauticPlugin\CustomObjectsBundle\Exception\NotFoundException;

class CustomItemImportModel extends FormModel
{
    /**
     * @var EntityManager
     */
    private $entityManager;

    /**
     * @var CustomItemModel
     */
    private $customItemModel;

    /**
     * @var FormatterHelper
     */
    private $formatterHelper;

    /**
     * @param EntityManager   $entityManager
     * @param CustomItemModel $customItemModel
     * @param FormatterHelper $formatterHelper
     */
    public function __construct(
        EntityManager $entityManager,
        CustomItemModel $customItemModel,
        FormatterHelper $formatterHelper
    ) {
        $this->entityManager   = $entityManager;
        $this->customItemModel = $customItemModel;
        $this->formatterHelper = $formatterHelper;
    }

    /**
     * @param Import       $import
     * @param mixed[]      $rowData
     * @param CustomObject $customObject
     *
     * @return bool updated = true, inserted = false
     */
    public function import(Import $import, array $rowData, CustomObject $customObject): bool
    {
        $matchedFields = $import->getMatchedFields();
        $customItem    = $this->getCustomItem($import, $customObject, $rowData);
        $merged        = (bool) $customItem->getId();
        $contactIds    = [];

        $this->setOwner($import, $customItem);

        foreach ($matchedFields as $csvField => $customFieldId) {
            $csvValue = $rowData[$csvField];

            if (0 === strcasecmp('linkedContactIds', $customFieldId)) {
                $contactIds = $this->formatterHelper->simpleCsvToArray($csvValue, 'int');

                continue;
            }

            if (0 === strcasecmp('customItemName', $customFieldId)) {
                $customItem->setName($csvValue);

                continue;
            }

            if (0 === strcasecmp('customItemId', $customFieldId)) {
                continue;
            }

            try {
                $customFieldValue = $customItem->findCustomFieldValueForFieldId((int) $customFieldId);
            } catch (NotFoundException $e) {
                $customFieldValue = $this->createNewCustomFieldValue($customObject, $customItem, (int) $customFieldId, $csvValue);
            }

            $customFieldValue->setValue($csvValue);
        }

        $customItem = $this->customItemModel->save($customItem);

        $this->linkContacts($customItem, $contactIds);

        return $merged;
    }

    /**
     * @param CustomObject $customObject
     * @param CustomItem   $customItem
     * @param int          $customFieldId
     * @param mixed        $csvValue
     *
     * @return CustomFieldValueInterface
     */
    private function createNewCustomFieldValue(CustomObject $customObject, CustomItem $customItem, int $customFieldId, $csvValue): CustomFieldValueInterface
    {
        foreach ($customObject->getCustomFields() as $customField) {
            if ($customField->getId() === (int) $customFieldId) {
                $fieldType        = $customField->getTypeObject();
                $customFieldValue = $fieldType->createValueEntity($customField, $customItem, $csvValue);
                $customItem->addCustomFieldValue($customFieldValue);

                return $customFieldValue;
            }
        }

        throw new NotFoundException("Custom field field {$customFieldId} was not found.");
    }

    /**
     * @param CustomItem $customItem
     * @param int[]      $contactIds
     *
     * @return CustomItem
     */
    private function linkContacts(CustomItem $customItem, array $contactIds): CustomItem
    {
        foreach ($contactIds as $contactId) {
            $xref = $this->customItemModel->linkEntity($customItem, 'contact', $contactId);
            $customItem->addContactReference($xref);
        }

        return $customItem;
    }

    /**
     * @param Import     $import
     * @param CustomItem $customItem
     *
     * @return CustomItem
     */
    private function setOwner(Import $import, CustomItem $customItem): CustomItem
    {
        $owner = $import->getDefault('owner');

        if ($owner) {
            /** @var User $user */
            $user = $this->entityManager->find(User::class, $owner);

            $customItem->setCreatedBy($user);
        }

        return $customItem;
    }

    /**
     * @param Import       $import
     * @param CustomObject $customObject
     * @param mixed[]      $rowData
     *
     * @return CustomItem
     */
    private function getCustomItem(Import $import, CustomObject $customObject, array $rowData): CustomItem
    {
        $matchedFields = $import->getMatchedFields();
        $customItem    = new CustomItem($customObject);
        $idKey         = array_search(
            strtolower('customItemId'),
            array_map('strtolower', $matchedFields),
            true
        );

        if (false !== $idKey) {
            try {
                $customItem = $this->customItemModel->fetchEntity((int) $rowData[$idKey]);
            } catch (NotFoundException $e) {
            }
        }

        return $customItem;
    }
}
