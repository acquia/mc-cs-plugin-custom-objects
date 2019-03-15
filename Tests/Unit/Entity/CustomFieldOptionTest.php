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

class CustomFieldOptionTest extends \PHPUnit_Framework_TestCase
{
    public function testConstructorAndToArray()
    {
        $id          = 1;
        $customField = new CustomField();
        $label       = 'label';
        $value       = 'value';

        $optionArray = [
            'id'          => $id,
            'customField' => $customField,
            'label'       => $label,
            'value'       => $value,
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
    }
}
