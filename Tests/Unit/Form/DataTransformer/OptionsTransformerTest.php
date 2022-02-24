<?php

declare(strict_types=1);

namespace MauticPlugin\CustomObjectsBundle\Tests\Unit\Form\DataTransformer;

use Doctrine\Common\Collections\ArrayCollection;
use MauticPlugin\CustomObjectsBundle\Entity\CustomFieldOption;
use MauticPlugin\CustomObjectsBundle\Form\DataTransformer\OptionsTransformer;

class OptionsTransformerTest extends \PHPUnit\Framework\TestCase
{
    public function testTransform(): void
    {
        $transformer = new OptionsTransformer();
        $this->assertSame(['list' => []], $transformer->transform(null));
        $this->assertSame(['list' => []], $transformer->transform(new ArrayCollection()));

        $options = [
            0 => 11,
            1 => 221,
        ];

        $optionsInCollection = new ArrayCollection($options);

        $expectedOptions = ['list' => $options];

        $this->assertSame($expectedOptions, $transformer->transform($optionsInCollection));
    }

    public function testReverseTransform(): void
    {
        $key     = 0;
        $options = [
            'list' => [
                $key => [
                    'label' => 'label1',
                    'value' => 'value1',
                ],
            ],
        ];

        $transformer = new OptionsTransformer();
        $collecction = $transformer->reverseTransform($options);

        $this->assertInstanceOf(ArrayCollection::class, $collecction);

        $receivedOption = $collecction->first();
        $this->assertInstanceOf(CustomFieldOption::class, $receivedOption);

        $this->assertSame($options['list'][0]['label'], $receivedOption->getLabel());
        $this->assertSame($options['list'][0]['value'], $receivedOption->getValue());
        $this->assertSame($key, $receivedOption->getOrder());

        // Test remove options with empty value or label
        $options1                        = $options;
        $options1['list'][$key]['label'] = '';

        $collecction = $transformer->reverseTransform($options1);

        $this->assertInstanceOf(ArrayCollection::class, $collecction);
        $this->assertSame(0, $collecction->count());

        $options1                        = $options;
        $options1['list'][$key]['value'] = '';

        $collecction = $transformer->reverseTransform($options1);

        $this->assertInstanceOf(ArrayCollection::class, $collecction);
        $this->assertSame(0, $collecction->count());

        // Test remove duplicate options
        $options['list'][$key + 1] = $options['list'][$key];

        $collecction = $transformer->reverseTransform($options);

        $this->assertInstanceOf(ArrayCollection::class, $collecction);
        $this->assertSame(1, $collecction->count());
    }
}
