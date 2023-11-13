<?php

declare(strict_types=1);

namespace MauticPlugin\CustomObjectsBundle\Tests\Functional\DataFixtures\Traits;

use MauticPlugin\CustomObjectsBundle\Entity\CustomField;
use MauticPlugin\CustomObjectsBundle\Entity\CustomFieldOption;
use MauticPlugin\CustomObjectsBundle\Entity\CustomItem;
use MauticPlugin\CustomObjectsBundle\Entity\CustomObject;
use MauticPlugin\CustomObjectsBundle\Model\CustomItemModel;
use MauticPlugin\CustomObjectsBundle\Model\CustomObjectModel;
use MauticPlugin\CustomObjectsBundle\Provider\CustomFieldTypeProvider;
use Symfony\Component\DependencyInjection\ContainerInterface;

trait CustomObjectsTrait
{
    private function createCustomObjectWithAllFields(ContainerInterface $container, string $customObjectName, ?callable $configureFieldCallback = null): CustomObject
    {
        /** @var CustomObjectModel $customObjectModel */
        $customObjectModel = $container->get('mautic.custom.model.object');
        $customObject      = new CustomObject();

        /** @var CustomFieldTypeProvider $customFieldTypeProvider */
        $customFieldTypeProvider = $container->get('custom_field.type.provider');
        $customFieldTypes        = $customFieldTypeProvider->getTypes();

        $customObject->setNameSingular($customObjectName);
        $customObject->setNamePlural("{$customObjectName}s");

        foreach ($customFieldTypes as $customFieldType) {
            $customField = new CustomField();
            $customField->setTypeObject($customFieldType);
            $customField->setType($customFieldType->getKey());
            $customField->setLabel("{$customFieldType->getName()} Test Field");

            if ($customField->isChoiceType()) {
                $this->addFieldOption($customField, 'Option A', 'option_a');
                $this->addFieldOption($customField, 'Option B', 'option_b');
            }

            if (null !== $configureFieldCallback) {
                $configureFieldCallback($customField);
            }

            $customObject->addCustomField($customField);
        }

        $customObjectModel->save($customObject);

        return $customObject;
    }

    /**
     * @param array<string,mixed> $fieldValues
     */
    public function createCustomItem(ContainerInterface $container, CustomObject $customObject, string $name, array $fieldValues): CustomItem
    {
        $customItem = new CustomItem($customObject);
        $customItem->setName($name);

        foreach ($fieldValues as $fieldAlias => $fielValue) {
            $customItem->addCustomFieldValue($customItem->createNewCustomFieldValueByFieldAlias($fieldAlias, $fielValue));
        }

        /** @var CustomItemModel $customItemModel */
        $customItemModel = $container->get('mautic.custom.model.item');

        return $customItemModel->save($customItem);
    }

    private function addFieldOption(CustomField $customField, string $label, string $value): void
    {
        $option = new CustomFieldOption();
        $option->setCustomField($customField);
        $option->setLabel($label);
        $option->setValue($value);
        $customField->addOption($option);
    }
}
