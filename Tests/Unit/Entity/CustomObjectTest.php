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

use MauticPlugin\CustomObjectsBundle\Entity\CustomObject;
use MauticPlugin\CustomObjectsBundle\Entity\CustomField;
use Mautic\CategoryBundle\Entity\Category;
use Doctrine\Common\Collections\ArrayCollection;

class CustomObjectTest extends \PHPUnit_Framework_TestCase
{
    public function testClone(): void
    {
        $object = new CustomObject();
        $object->setAlias('object-a');
        $object->setNameSingular('Object A');
        $clone = clone $object;

        $this->assertNull($clone->getAlias());
        $this->assertSame('Object A', $clone->getNameSingular());
    }

    public function testGettersSetters(): void
    {
        $category = new Category();
        $object   = new CustomObject();
        $fields   = new ArrayCollection();

        $object->setCategory($category);
        $object->setLanguage('sk');
        $object->setCustomFields($fields);

        $this->assertSame($category, $object->getCategory());
        $this->assertSame('sk', $object->getLanguage());
        $this->assertSame($fields, $object->getCustomFields());
    }

    public function testCustomFieldChanges(): void
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
