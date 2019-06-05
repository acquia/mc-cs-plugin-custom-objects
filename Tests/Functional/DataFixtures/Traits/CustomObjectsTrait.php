<?php

declare(strict_types=1);

/*
 * @copyright   2019 Mautic Inc. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.com
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\CustomObjectsBundle\Tests\Functional\DataFixtures\Traits;

use Symfony\Component\DependencyInjection\ContainerInterface;
use MauticPlugin\CustomObjectsBundle\Entity\CustomField;
use MauticPlugin\CustomObjectsBundle\Provider\CustomFieldTypeProvider;
use MauticPlugin\CustomObjectsBundle\Model\CustomObjectModel;
use MauticPlugin\CustomObjectsBundle\Entity\CustomObject;
use MauticPlugin\CustomObjectsBundle\Entity\CustomFieldOption;

trait CustomObjectsTrait
{
    /**
     * @param ContainerInterface $container
     * @param string             $name
     *
     * @return CustomObject
     */
    private function createCustomObjectWithAllFields(ContainerInterface $container, string $name): CustomObject
    {
        /** @var CustomObjectModel $customObjectModel */
        $customObjectModel = $container->get('mautic.custom.model.object');
        $customObject      = new CustomObject();

        /** @var CustomFieldTypeProvider $customFieldTypeProvider */
        $customFieldTypeProvider = $container->get('custom_field.type.provider');
        $customFieldTypes        = $customFieldTypeProvider->getTypes();

        $customObject->setNameSingular($name);
        $customObject->setNamePlural("{$name}s");

        foreach ($customFieldTypes as $customFieldType) {
            $customField = new CustomField();
            $customField->setTypeObject($customFieldType);
            $customField->setType($customFieldType->getKey());
            $customField->setLabel("{$customFieldType->getName()} Test Field");

            if ($customField->isChoiceType()) {
                $this->addFieldOption($customField, 'Option A', 'option_a');
                $this->addFieldOption($customField, 'Option B', 'option_b');
            }

            $customObject->addCustomField($customField);
        }

        $customObjectModel->save($customObject);

        return $customObject;
    }

    /**
     * @param CustomField $customField
     * @param string      $label
     * @param string      $value
     */
    private function addFieldOption(CustomField $customField, string $label, string $value): void
    {
        $option = new CustomFieldOption();
        $option->setCustomField($customField);
        $option->setLabel($label);
        $option->setValue($value);
        $customField->addOption($option);
    }
}
