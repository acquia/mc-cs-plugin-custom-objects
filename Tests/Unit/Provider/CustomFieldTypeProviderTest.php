<?php

declare(strict_types=1);

namespace MauticPlugin\CustomObjectsBundle\Tests\Unit\Provider;

use Mautic\CoreBundle\Translation\Translator;
use Mautic\LeadBundle\Provider\FilterOperatorProviderInterface;
use MauticPlugin\CustomObjectsBundle\CustomFieldType\DateTimeType;
use MauticPlugin\CustomObjectsBundle\CustomFieldType\DateType;
use MauticPlugin\CustomObjectsBundle\CustomFieldType\TextType;
use MauticPlugin\CustomObjectsBundle\Exception\NotFoundException;
use MauticPlugin\CustomObjectsBundle\Provider\CustomFieldTypeProvider;
use Symfony\Contracts\Translation\TranslatorInterface;

class CustomFieldTypeProviderTest extends \PHPUnit\Framework\TestCase
{
    public function testWorkflow(): void
    {
        $customFieldTypeProvider = new CustomFieldTypeProvider();
        $filterOperatorProvider  = $this->createMock(FilterOperatorProviderInterface::class);
        $textType                = new TextType($this->createMock(TranslatorInterface::class), $filterOperatorProvider);

        $customFieldTypeProvider->addType($textType);

        $this->assertSame($textType, $customFieldTypeProvider->getType('text'));

        $this->expectException(NotFoundException::class);
        $customFieldTypeProvider->getType('unicorn');
    }

    public function testKeyTypeMapping(): void
    {
        $filterOperatorProvider = $this->createMock(FilterOperatorProviderInterface::class);
        $mockTranslator         = $this->createMock(Translator::class);
        $typeProvider           = new CustomFieldTypeProvider();
        $match                  = [];
        $typesArray             = [
            'custom.field.type.date'      => new DateType($mockTranslator, $filterOperatorProvider),
            'custom.field.type.datetime'  => new DateTimeType($mockTranslator, $filterOperatorProvider),
        ];

        $mockTranslator->expects($this->exactly(count($typesArray)))
            ->method('trans')
            ->willReturnCallback(function ($argument) {
                return $argument;
            });

        foreach ($typesArray as $type) {
            $typeProvider->addType($type);
            $match[$type->getName()] = $type->getKey();
        }

        $mapping = $typeProvider->getKeyTypeMapping();

        $this->assertSame(count($typesArray), count($mapping));
        $this->assertSame($match, $mapping);
    }
}
