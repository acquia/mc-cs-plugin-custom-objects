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

namespace MauticPlugin\CustomObjectsBundle\Tests\Unit\Entity\CustomField;

use MauticPlugin\CustomObjectsBundle\Entity\CustomField;
use MauticPlugin\CustomObjectsBundle\Entity\CustomField\Option;

class OptionTest extends \PHPUnit_Framework_TestCase
{
    public function testConstructor()
    {
        $id          = 1;
        $customField = new CustomField();
        $label       = 'label';
        $value       = 'value';

        $option = [
            'id'          => $id,
            'customField' => $customField,
            'label'       => $label,
            'value'       => $value,
        ];

        $option = new Option($option);

        $this->assertSame($id, $option->getId());
        $this->assertSame($customField, $option->getCustomField());
        $this->assertSame($label, $option->getLabel());
        $this->assertSame($value, $option->getValue());
    }

    public function testGettersSetters()
    {
        $option = new Option();

        $label = 'label';
        $option->setLabel($label);
        $this->assertSame($label, $option->getLabel());

        $value = 'value';
        $option->setValue($value);
        $this->assertSame($value, $option->getValue());
    }
}
