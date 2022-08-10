<?php

declare(strict_types=1);

namespace MauticPlugin\CustomObjectsBundle\Model;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManager;
use Mautic\CoreBundle\Model\FormModel;
use Mautic\CoreBundle\Templating\Helper\FormatterHelper;
use Mautic\LeadBundle\Entity\Import;
use Mautic\UserBundle\Entity\User;
use MauticPlugin\CustomObjectsBundle\Entity\CustomItem;
use MauticPlugin\CustomObjectsBundle\Entity\CustomObject;
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
     * @param mixed[] $rowData
     *
     * @return bool updated = true, inserted = false
     */
    public function import(Import $import, array $rowData, CustomObject $customObject): bool
    {
        $matchedFields = $import->getMatchedFields();
        $customItem    = $this->getCustomItem($import, $customObject, $rowData);
        $merged        = (bool) $customItem->getId();
        $contactIds    = [];
        $uniqueIdentifierFields = $customObject->getFieldsIsUniqueIdentifier();
        $uniqueKey = null;

        $this->setOwner($import, $customItem);

        if(!empty($uniqueIdentifierFields)){
            $uniqueKey = $this->getUniqueKey($uniqueIdentifierFields, $rowData);
        }

        foreach ($matchedFields as $csvField => $customFieldId) {
            if (!isset($rowData[$csvField])) {
                continue;
            }

            $csvValue = $rowData[$csvField];

            if (is_string($customFieldId)) {
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
            }

            try {
                $customFieldValue = $customItem->findCustomFieldValueForFieldId((int) $customFieldId);
            } catch (NotFoundException $e) {
                $customFieldValue = $customItem->createNewCustomFieldValueByFieldId((int) $customFieldId, $csvValue);
            }

            $customFieldValue->setValue($csvValue);
        }

        $customItem->setDefaultValuesForMissingFields();

        if($uniqueKey) {
            $customItemUpdateId = null;
            foreach($this->customItemModel->getAllCustomItemsForCustomObject($customObject->getId()) as $existingCustomItem){
                //TODO: getUniqueKey() definition to be reviewed. Also, I am not sure the (array) typecasting works here correctly
                if($this->getUniqueKey($uniqueIdentifierFields, (array) $existingCustomItem) == $this->getUniqueKey($uniqueIdentifierFields, (array) $customItem)){
                    $customItemUpdateId = $existingCustomItem->getId();
                    break;
                }
            }
            //TODO: insert and update are not real functions. This is for a skeletal structure understanding purposes. To be replaced
            $customItemUpdateId == null ? $customItem->insert() : $customItem->update($customItemUpdateId);
        }
        //TODO: to be covered in the insert update scenario
        $customItem = $this->customItemModel->save($customItem);
        //TODO: contacts to be linked on the basis of uniquekey if available
        $this->linkContacts($customItem, $contactIds);

        return $merged;
    }

    /**
     * @param int[] $contactIds
     */
    private function linkContacts(CustomItem $customItem, array $contactIds): CustomItem
    {
        foreach ($contactIds as $contactId) {
            $xref = $this->customItemModel->linkEntity($customItem, 'contact', $contactId);
            $customItem->addContactReference($xref);
        }

        return $customItem;
    }

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
     * @param mixed[] $rowData
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
                $customItem = $this->customItemModel->populateCustomFields($customItem);
            } catch (NotFoundException $e) {
            }
        }

        return $customItem;
    }

    /**
     * @param \Doctrine\Common\Collections\Collection $uniqueIdentifierFields
     * @param array $rowData
     * @return false|string
     */
    private function getUniqueKey(\Doctrine\Common\Collections\Collection $uniqueIdentifierFields, array $rowData)
    {
        $compositeKeyString = "";
        foreach ($uniqueIdentifierFields as $uniqueIdentifierField) {
            $compositeKeyString = $compositeKeyString . $rowData[$uniqueIdentifierField]; //don't really  know how $rowdata actually works
        }
        return hash('sha256', $compositeKeyString);
    }
}
