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

namespace MauticPlugin\CustomObjectsBundle\Tests\Unit\Entity;

use MauticPlugin\CustomObjectsBundle\Entity\CustomField;
use MauticPlugin\CustomObjectsBundle\Entity\CustomFieldOption;
use Symfony\Component\Validator\Constraints\NotNull;
use Symfony\Component\Validator\Mapping\ClassMetadata;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Length;

class CustomFieldOptionTest extends \PHPUnit_Framework_TestCase
{
    public function testLoadValidatorMetadata(): void
    {
        $metadata = $this->createMock(ClassMetadata::class);
        $object   = new CustomFieldOption();

        $metadata->expects($this->exactly(5))
            ->method('addPropertyConstraint')
            ->withConsecutive(
                ['label', $this->isInstanceOf(NotBlank::class)],
                ['label', $this->isInstanceOf(Length::class)],
                ['value', $this->isInstanceOf(NotNull::class)],
                ['value', $this->isInstanceOf(Length::class)],
                ['order', $this->isInstanceOf(NotNull::class)]
            );

        $object->loadValidatorMetadata($metadata);
    }

    public function testConstructorAndToArray()
    {
        $customField = new CustomField();
        $label       = 'label';
        $value       = 'value';
        $order       = 1;

        $optionArray = [
            'customField' => $customField,
            'label'       => $label,
            'value'       => $value,
            'order'       => $order,
        ];

        $option = new CustomFieldOption($optionArray);

        // Because has no ID and null values are filtered with array_filter
        unset($optionArray['customField']);

        $this->assertSame($optionArray, $option->__toArray());
    }

    public function testGettersSetters()
    {
        $option = new CustomFieldOption();

        $label = 'label';
        $option->setLabel($label);
        $this->assertSame($label, $option->getLabel());

        $value = 'value';
        $option->setValue($value);
        $this->assertSame($value, $option->getValue());

        $order = 3;
        $option->setOrder($order);
        $this->assertSame($order, $option->getOrder());

        $customField = new CustomField();
        $this->assertNull($option->getCustomField());
        $option->setCustomField($customField);
        $this->assertSame($customField, $option->getCustomField());

        $order = 1;
        $option->setOrder($order);
        $this->assertSame($order, $option->getOrder());
    }

    public function testArrayAccessor()
    {
        $option = new CustomFieldOption();

        $option->setLabel('label');

        $this->assertSame('label', $option['label']);
        $this->assertSame('label', $option->getLabel());

        $option['label'] = 'babel';

        $this->assertSame('babel', $option['label']);
        $this->assertSame('babel', $option->getLabel());

        unset($option['label']);

        $this->assertNull($option['label']);
        $this->assertSame('', $option->getLabel());
    }
}
