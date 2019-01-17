<?php

/*
 * @copyright   2019 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\CustomObjectsBundle\Tests\Entity;

use MauticPlugin\CustomObjectsBundle\Entity\CustomFieldFactory;

class CustomFieldFactoryTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var array
     */
    var $definedTypes = [
            'text',
            'int',
        ];

    public function testCreate()
    {
        $factory = new CustomFieldFactory();

        foreach ($this->definedTypes as $type) {
            $customField = $factory->create($type);
            $this->assertSame($type, $customField->getType()->getKey());
        }

        $this->expectException(\InvalidArgumentException::class);
        $factory->create('undefined_type');
    }
}
