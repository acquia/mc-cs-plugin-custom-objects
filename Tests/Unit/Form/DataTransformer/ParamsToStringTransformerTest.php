<?php

declare(strict_types=1);

namespace MauticPlugin\CustomObjectsBundle\Tests\Unit\Form\DataTransformer;

use JMS\Serializer\SerializerInterface;
use MauticPlugin\CustomObjectsBundle\Entity\CustomField\Params;
use MauticPlugin\CustomObjectsBundle\Form\DataTransformer\ParamsToStringTransformer;

class ParamsToStringTransformerTest extends \PHPUnit\Framework\TestCase
{
    public function testTransform(): void
    {
        $params = $this->createMock(Params::class);
        $params
            ->expects($this->once())
            ->method('__toArray')
            ->willReturn([]);

        $serializer = $this->createMock(SerializerInterface::class);
        $serializer
            ->expects($this->once())
            ->method('serialize')
            ->willReturn('[]');

        $transformer = new ParamsToStringTransformer($serializer);
        $this->assertSame('[]', $transformer->transform($params));

        $params     = null;
        $serializer = $this->createMock(SerializerInterface::class);
        $serializer
            ->expects($this->never())
            ->method('serialize');

        $transformer = new ParamsToStringTransformer($serializer);
        $this->assertSame('[]', $transformer->transform($params));

        $params     = [];
        $serializer = $this->createMock(SerializerInterface::class);
        $serializer
            ->expects($this->once())
            ->method('serialize')
            ->willReturn('[]');

        $transformer = new ParamsToStringTransformer($serializer);
        $this->assertSame('[]', $transformer->transform($params));
    }

    public function testReverseTransform(): void
    {
        $params      = '[]';
        $serializer  = $this->createMock(SerializerInterface::class);
        $transformer = new ParamsToStringTransformer($serializer);
        $params      = $transformer->reverseTransform($params);
        $this->assertInstanceOf(Params::class, $params);
        $this->assertSame([], $params->__toArray());
    }
}
