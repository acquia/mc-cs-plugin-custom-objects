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

use MauticPlugin\CustomObjectsBundle\Entity\CustomObject;
use MauticPlugin\CustomObjectsBundle\Entity\CustomField;

class CustomObjectTest extends \PHPUnit_Framework_TestCase
{
    public function testCustomFieldChanges()
    {
        $object        = new CustomObject();
        $modifiedField = $this->createMock(CustomField::class);
        $createdField  = $this->createMock(CustomField::class);
        $deletedField  = $this->createMock(CustomField::class);

        $modifiedField->method('getId')->willReturn(13);
        $modifiedField->method('toArray')->willReturnOnConsecutiveCalls(
            ['id' => 13, 'label' => 'Field A'],        // initial value
            ['id' => 13, 'label' => 'Field A changed'] // new value
        );
        $createdField->method('toArray')->willReturn(['id' => null, 'label' => 'Field B']);
        $deletedField->method('toArray')->willReturn(['id' => 44, 'label' => 'Field C']);
        $deletedField->method('getId')->willReturn(44);

        $object->addCustomField($modifiedField);
        $object->addCustomField($deletedField);
        $object->createFieldsSnapshot();
        $object->addCustomField($createdField);
        $object->removeCustomField($deletedField);
        $object->recordCustomFieldChanges();

        $this->assertSame([
            'customfield:13:label' => [
                'Field A',
                'Field A changed',
            ],
            'customfield:temp_2:id' => [
                null,
                'temp_2',
            ],
            'customfield:temp_2:label' => [
                null,
                'Field B',
            ],
            'customfield:44' => [
                null,
                'deleted',
            ],
        ], $object->getChanges());
    }
}
