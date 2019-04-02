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

namespace MauticPlugin\CustomObjectsBundle\Tests\Unit\Provider;

use MauticPlugin\CustomObjectsBundle\Provider\CustomFieldTypeProvider;
use Symfony\Component\Translation\TranslatorInterface;
use MauticPlugin\CustomObjectsBundle\CustomFieldType\TextType;
use MauticPlugin\CustomObjectsBundle\Exception\NotFoundException;

class CustomFieldTypeProviderTest extends \PHPUnit_Framework_TestCase
{
    public function testWorkflow(): void
    {
        $customFieldTypeProvider = new CustomFieldTypeProvider();
        $textType               = new TextType($this->createMock(TranslatorInterface::class));

        $customFieldTypeProvider->addType($textType);

        $this->assertSame($textType, $customFieldTypeProvider->getType('text'));

        $this->expectException(NotFoundException::class);
        $customFieldTypeProvider->getType('unicorn');
    }
}
