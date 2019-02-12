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
use MauticPlugin\CustomObjectsBundle\Repository\CustomItemRepository;
use MauticPlugin\CustomObjectsBundle\Provider\CustomItemPermissionProvider;
use MauticPlugin\CustomObjectsBundle\Entity\CustomObject;
use MauticPlugin\CustomObjectsBundle\Entity\CustomField;
use Mautic\LeadBundle\Entity\Import;
use Mautic\CoreBundle\Templating\Helper\FormatterHelper;
use Mautic\UserBundle\Entity\User;
use MauticPlugin\CustomObjectsBundle\Model\CustomItemImportModel;
use MauticPlugin\CustomObjectsBundle\Entity\CustomFieldValueInterface;
use MauticPlugin\CustomObjectsBundle\Exception\NotFoundException;

class CustomItemImportModel extends FormModel
{
    /**
     * @var EntityManager
     */
    private $entityManager;

    /**
     * @var CustomItemRepository
     */
    private $customItemRepository;

    /**
     * @var CustomItemPermissionProvider
     */
    private $permissionProvider;

    /**
     * @var CustomItemModel
     */
    private $customItemModel;

    /**
     * @var FormatterHelper
     */
    private $formatterHelper;

    /**
     * @param EntityManager $entityManager
     * @param CustomItemRepository $customItemRepository
     * @param CustomItemPermissionProvider $permissionProvider
     * @param CustomItemModel $customItemModel
     * @param FormatterHelper $formatterHelper
     */
    public function __construct(
        EntityManager $entityManager,
        CustomItemRepository $customItemRepository,
        CustomItemPermissionProvider $permissionProvider,
        CustomItemModel $customItemModel,
        FormatterHelper $formatterHelper
    )
    {
        $this->entityManager        = $entityManager;
        $this->customItemRepository = $customItemRepository;
        $this->permissionProvider   = $permissionProvider;
        $this->customItemModel      = $customItemModel;
        $this->formatterHelper      = $formatterHelper;
    }

    /**
     * @param Import $import
     * @param array $rowData
     * @param CustomObject $customObject
     * 
     * @return boolean updated = true, inserted = false
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

            if (strcasecmp('linkedContactIds', $customFieldId) === 0) {
                $contactIds = $this->formatterHelper->simpleCsvToArray($csvValue, 'int');
                continue;
            }

            if (strcasecmp('customItemName', $customFieldId) === 0) {
                $customItem->setName($csvValue);
                continue;
            }

            if (strcasecmp('customItemId', $customFieldId) === 0) {
                continue;
            }

            $customFieldValue = $customItem->findCustomFieldValueForFieldId((int) $customFieldId);

            if (!$customFieldValue) {
                $customFieldValue = $this->createNewCustomFieldValue($customObject, $customItem, (int) $customFieldId, $csvValue);
            } else {
                $customFieldValue->updateThisEntityManually();
            }

            $customFieldValue->setValue($csvValue);
        }

        $this->customItemModel->save($customItem);

        $this->linkContacts($customItem, $contactIds);

        return $merged;
    }

    /**
     *
     * @param CustomObject $customObject
     * @param CustomItem $customItem
     * @param integer $customFieldId
     * @param int $csvValue
     * 
     * @return CustomFieldValueInterface
     */
    private function createNewCustomFieldValue(CustomObject $customObject, CustomItem $customItem, int $customFieldId, $csvValue): CustomFieldValueInterface
    {
        foreach ($customObject->getCustomFields() as $customField) {
            if ($customField->getId() === (int) $customFieldId) {
                $fieldType  = $customField->getTypeObject();
                $customFieldValue = $fieldType->createValueEntity($customField, $customItem, $csvValue);
                $customItem->addCustomFieldValue($customFieldValue);
            }
        }

        return $customFieldValue;
    }

    /**
     * @param CustomItem $customItem
     * @param array $contactIds
     * 
     * @return CustomItem
     */
    private function linkContacts(CustomItem $customItem, array $contactIds): CustomItem
    {
        foreach ($contactIds as $contactId) {
            $this->customItemModel->linkContact($customItem->getId(), $contactId);
        }

        return $customItem;
    }

    /**
     * @param Import $import
     * @param CustomItem $customItem
     * 
     * @return CustomItem
     */
    private function setOwner(Import $import, CustomItem $customItem): CustomItem
    {
        if ($owner = $import->getDefault('owner')) {
            $customItem->setCreatedBy($this->entityManager->find(User::class, $owner));
        }

        return $customItem;
    }

    /**
     * @param Import $import
     * @param CustomObject $customObject
     * @param array $rowData
     * 
     * @return CustomItem
     */
    private function getCustomItem(Import $import, CustomObject $customObject, array $rowData): CustomItem
    {
        $matchedFields = $import->getMatchedFields();
        $customItem    = new CustomItem($customObject);
        $idKey         = array_search(
            strtolower('customItemId'),
            array_map('strtolower', $matchedFields)
        );

        if (false !== $idKey) {
            try {
                $customItem = $this->customItemModel->fetchEntity((int) $rowData[$idKey]);
                $customItem = $this->customItemModel->populateCustomFields($customItem);
            } catch (NotFoundException $e) {}
        }

        return $customItem;
    }
}
