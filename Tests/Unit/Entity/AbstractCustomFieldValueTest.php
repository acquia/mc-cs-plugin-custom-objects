<?php

declare(strict_types=1);

/*
 * @copyright   2019 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc
 *
 * @link        https://mautic.com
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\CustomObjectsBundle\Tests\Unit\Entity;

use MauticPlugin\CustomObjectsBundle\Entity\CustomField;
use MauticPlugin\CustomObjectsBundle\Entity\CustomItem;
use MauticPlugin\CustomObjectsBundle\Entity\CustomObject;
use MauticPlugin\CustomObjectsBundle\Entity\AbstractCustomFieldValue;

class AbstractCustomFieldValueTest extends \PHPUnit_Framework_TestCase
{
    public function testGettersSetters(): void
    {
        $customObject    = new CustomObject();
        $customField     = $this->createMock(CustomField::class);
        $customItem      = new CustomItem($customObject);
        $abstractCFValue = $this->getMockForAbstractClass(
            AbstractCustomFieldValue::class,
            [$customField, $customItem]
        );

        $customField->expects($this->once())
            ->method('getId')
            ->willReturn(123);

        $this->assertSame($customField, $abstractCFValue->getCustomField());
        $this->assertSame($customItem, $abstractCFValue->getCustomItem());
        $this->assertSame(123, $abstractCFValue->getId());

        $this->expectException(\Throwable::class);
        $abstractCFValue->addValue();
    }
}
