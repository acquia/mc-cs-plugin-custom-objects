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

namespace MauticPlugin\CustomObjectsBundle\Tests\Entity;

use MauticPlugin\CustomObjectsBundle\Entity\CustomItem;
use MauticPlugin\CustomObjectsBundle\Entity\CustomObject;
use MauticPlugin\CustomObjectsBundle\Entity\CustomField;
use Doctrine\Common\Collections\ArrayCollection;

class CustomObjectTest extends \PHPUnit_Framework_TestCase
{
    public function testCustomFieldChanges()
    {
        $object = new CustomObject();
        $fieldA = $this->createMock(CustomField::class);
        $fieldB = $this->createMock(CustomField::class);

        $fieldA->method('getId')->willReturn(13);
        $fieldA->method('toArray')->willReturnOnConsecutiveCalls(
            ['id' => 13, 'label' => 'Field A'],        // initial value
            ['id' => 13, 'label' => 'Field A changed'] // new value
        );
        $fieldB->method('toArray')->willReturn(['id' => null, 'label' => 'Field B']);

        $object->addCustomField($fieldA);
        $object->createFieldsSnapshot();
        $object->addCustomField($fieldB);
        $object->recordCustomFieldChanges();

        $this->assertSame([
            'customfield:13:label' => [
                'Field A',
                'Field A changed',
            ],
            'customfield:temp_1:id' => [
                null,
                'temp_1',
            ],
            'customfield:temp_1:label' => [
                null,
                'Field B',
            ],
        ], $object->getChanges());
    }
}
