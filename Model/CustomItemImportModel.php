<?php

declare(strict_types=1);

namespace MauticPlugin\CustomObjectsBundle\Model;

use Doctrine\ORM\EntityManager;
use Mautic\CoreBundle\Model\FormModel;
use Mautic\CoreBundle\Templating\Helper\FormatterHelper;
use Mautic\LeadBundle\Entity\Import;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\UserBundle\Entity\User;
use MauticPlugin\CustomObjectsBundle\DTO\ImportLogDTO;
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
    public function import(Import $import, array $rowData, CustomObject $customObject, ImportLogDTO $importLogDto = null): bool
    {
        $matchedFields = $import->getMatchedFields();
        $customItem    = $this->getCustomItem($import, $customObject, $rowData);
        $merged        = (bool) $customItem->getId();
        $contactIds    = [];

        $this->setOwner($import, $customItem);
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

        $customItem = $this->customItemModel->save($customItem);
        if ($customItem->hasBeenUpdated()) {
            $merged = true;
        }

        $this->linkContacts($customItem, $contactIds, $importLogDto);

        return $merged;
    }

    /**
     * @param int[] $contactIds
     */
    private function linkContacts(CustomItem $customItem, array $contactIds, ?ImportLogDTO $importLogDto): void
    {
        foreach ($contactIds as $contactId) {
            $leadRepository = $this->entityManager->getRepository(Lead::class);
            if (method_exists($leadRepository, 'exists') && !$leadRepository->exists((string) $contactId)) {
                if ($importLogDto) {
                    $importLogDto->addWarning(
                        $this->translator->trans('custom.item.import.invalid.contactid.for.link', [
                            '%contactId%'    => $contactId,
                            '%customItemId%' => $customItem->getId(),
                        ])
                    );
                }
                continue;
            }

            $xref = $this->customItemModel->linkEntity($customItem, 'contact', $contactId);
            $customItem->addContactReference($xref);
        }
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
}
