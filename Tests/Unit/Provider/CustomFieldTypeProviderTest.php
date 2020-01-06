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

namespace MauticPlugin\CustomObjectsBundle\Tests\Unit\Provider;

use Mautic\CoreBundle\Translation\Translator;
use MauticPlugin\CustomObjectsBundle\CustomFieldType\DateTimeType;
use MauticPlugin\CustomObjectsBundle\CustomFieldType\DateType;
use MauticPlugin\CustomObjectsBundle\Provider\CustomFieldTypeProvider;
use Symfony\Component\Translation\TranslatorInterface;
use MauticPlugin\CustomObjectsBundle\CustomFieldType\TextType;
use MauticPlugin\CustomObjectsBundle\Exception\NotFoundException;

class CustomFieldTypeProviderTest extends \PHPUnit\Framework\TestCase
{
    public function testWorkflow(): void
    {
        $customFieldTypeProvider = new CustomFieldTypeProvider();
        $textType                = new TextType($this->createMock(TranslatorInterface::class));

        $customFieldTypeProvider->addType($textType);

        $this->assertSame($textType, $customFieldTypeProvider->getType('text'));

        $this->expectException(NotFoundException::class);
        $customFieldTypeProvider->getType('unicorn');
    }

    public function testKeyTypeMapping(): void
    {
        $mockTranslator = $this->createMock(Translator::class);

        $typesArray = [
            'custom.field.type.date'      => new DateType($mockTranslator),
            'custom.field.type.datetime'  => new DateTimeType($mockTranslator),
        ];

        $mockTranslator->expects($this->exactly(count($typesArray)))->method('trans')
            ->willReturnCallback(function ($argument) {return $argument; });

        $typeProvider = new CustomFieldTypeProvider();

        $match = [];

        foreach ($typesArray as $type) {
            $typeProvider->addType($type);
            $match[$type->getName()] = $type->getKey();
        }

        $mapping = $typeProvider->getKeyTypeMapping();

        $this->assertSame(count($typesArray), count($mapping));
        $this->assertSame($match, $mapping);
    }
}
