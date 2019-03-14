<?php

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

class OptionsToStringTransformerTest extends \PHPUnit_Framework_TestCase
{

    public function testTransform()
    {
        $options = $this->createMock(ArrayCollection::class);
        $options
            ->expects($this->once())
            ->method('toArray')
            ->willReturn([]);

        $serializer = $this->createMock(SerializerInterface::class);
        $serializer
            ->expects($this->once())
            ->method('serialize')
            ->willReturn('[]');

        $transformer = new OptionsToStringTransformer($serializer);
        $this->assertSame('[]', $transformer->transform($options));

        $options = null;
        $serializer = $this->createMock(SerializerInterface::class);
        $serializer
            ->expects($this->never())
            ->method('serialize');

        $transformer = new OptionsToStringTransformer($serializer);
        $this->assertSame('[]', $transformer->transform($options));
    }

    public function testReverseTransform()
    {
        $options = '[]';
        $serializer = $this->createMock(SerializerInterface::class);
        $transformer = new OptionsToStringTransformer($serializer);
        $options = $transformer->reverseTransform($options);
        $this->assertInstanceOf(ArrayCollection::class, $options);
        $this->assertSame([], $options->toArray());
    }
}
