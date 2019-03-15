<?php

declare(strict_types=1);

/*
 * @copyright   2019 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\CustomObjectsBundle\Tests\Unit\Entity;

use MauticPlugin\CustomObjectsBundle\Entity\CustomField;
use MauticPlugin\CustomObjectsBundle\Entity\CustomFieldValueOption;
use MauticPlugin\CustomObjectsBundle\Entity\CustomItem;
use MauticPlugin\CustomObjectsBundle\Entity\CustomObject;
use MauticPlugin\CustomObjectsBundle\Entity\CustomField\Option;

class CustomFieldValueOptionTest extends \PHPUnit_Framework_TestCase
{
    public function testGettersSetters(): void
    {
        $customObject = new CustomObject();
        $customField  = new CustomField();
        $option       = new Option();
        $option2      = new Option();
        $customItem   = new CustomItem($customObject);
        $optionValue  = new CustomFieldValueOption($customField, $customItem, $option);

        $this->assertSame($customField, $optionValue->getCustomField());
        $this->assertSame($customItem, $optionValue->getCustomItem());
        $this->assertSame($option, $optionValue->getOption());
        $this->assertSame($option, $optionValue->getValue());

        $optionValue->setOption($option2);

        $this->assertSame($option2, $optionValue->getValue());
    }
}
