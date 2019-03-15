<?php

declare(strict_types=1);

/*
* @copyright   2019 Mautic, Inc. All rights reserved
* @author      Mautic, Inc.
*
* @link        https://mautic.com
*
* @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
*/

namespace MauticPlugin\CustomObjectsBundle\Tests\Unit\Form\DataTransformer;

use Doctrine\Common\Collections\ArrayCollection;
use JMS\Serializer\SerializerInterface;
use MauticPlugin\CustomObjectsBundle\Form\DataTransformer\OptionsToStringTransformer;
use MauticPlugin\CustomObjectsBundle\Entity\CustomField\Option;

class OptionsToStringTransformerTest extends \PHPUnit_Framework_TestCase
{
    public function testTransformWithEmptyOptions(): void
    {
        $options    = null;
        $serializer = $this->createMock(SerializerInterface::class);
        $serializer
            ->expects($this->never())
            ->method('serialize');

        $transformer = new OptionsToStringTransformer($serializer);
        $this->assertSame('[]', $transformer->transform($options));
    }

    public function testTransform(): void
    {
        $option = new Option();
        $option->setLabel('Option A');
        $option->setValue('option_a');

        $options = new ArrayCollection([$option]);

        $serializer = $this->createMock(SerializerInterface::class);
        $serializer
            ->expects($this->once())
            ->method('serialize')
            ->with([[
                'label' => 'Option A',
                'value' => 'option_a',
            ]])
            ->willReturn('{"some": "json"}');

        $transformer = new OptionsToStringTransformer($serializer);
        $this->assertSame('{"some": "json"}', $transformer->transform($options));
    }

    public function testReverseTransform(): void
    {
        $options     = '[]';
        $serializer  = $this->createMock(SerializerInterface::class);
        $transformer = new OptionsToStringTransformer($serializer);
        $options     = $transformer->reverseTransform($options);
        $this->assertInstanceOf(ArrayCollection::class, $options);
        $this->assertSame([], $options->toArray());
    }
}
