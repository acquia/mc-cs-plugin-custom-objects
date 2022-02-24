<?php

declare(strict_types=1);

namespace MauticPlugin\CustomObjectsBundle\Tests\Unit\Form\DataTransformer;

use Doctrine\Common\Collections\ArrayCollection;
use JMS\Serializer\SerializerInterface;
use MauticPlugin\CustomObjectsBundle\Entity\CustomField;
use MauticPlugin\CustomObjectsBundle\Entity\CustomFieldOption;
use MauticPlugin\CustomObjectsBundle\Form\DataTransformer\OptionsToStringTransformer;
use MauticPlugin\CustomObjectsBundle\Model\CustomFieldModel;

class OptionsToStringTransformerTest extends \PHPUnit\Framework\TestCase
{
    public function testTransformWithEmptyOptions(): void
    {
        $options    = null;
        $serializer = $this->createMock(SerializerInterface::class);
        $model      = $this->createMock(CustomFieldModel::class);
        $serializer
            ->expects($this->never())
            ->method('serialize');

        $transformer = new OptionsToStringTransformer($serializer, $model);
        $this->assertSame('[]', $transformer->transform($options));
    }

    public function testTransform(): void
    {
        $option = new CustomFieldOption();
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

        $model = $this->createMock(CustomFieldModel::class);

        $transformer = new OptionsToStringTransformer($serializer, $model);
        $this->assertSame('{"some": "json"}', $transformer->transform($options));
    }

    public function testReverseTransform(): void
    {
        $options     = '[]';
        $serializer  = $this->createMock(SerializerInterface::class);
        $model       = $this->createMock(CustomFieldModel::class);
        $transformer = new OptionsToStringTransformer($serializer, $model);
        $options     = $transformer->reverseTransform($options);
        $this->assertInstanceOf(ArrayCollection::class, $options);
        $this->assertSame([], $options->toArray());

        $customFieldId = 2;
        $customField   = new CustomField();
        $label         = 'label';
        $value         = 'value';
        $options       = json_encode([[
            'customField' => $customFieldId,
            'label'       => $label,
            'value'       => $value,
        ]]);

        $serializer  = $this->createMock(SerializerInterface::class);
        $model       = $this->createMock(CustomFieldModel::class);
        $model
            ->expects($this->once())
            ->method('fetchEntity')
            ->with($customFieldId)
            ->willReturn($customField);

        $transformer = new OptionsToStringTransformer($serializer, $model);
        $options     = $transformer->reverseTransform($options);
        $this->assertInstanceOf(ArrayCollection::class, $options);
        $option = $options->first();
        $this->assertInstanceOf(CustomFieldOption::class, $option);
        $this->assertSame($customField, $option->getCustomField());
        $this->assertSame($label, $option->getLabel());
        $this->assertSame($value, $option->getValue());

        // Without custom field id
        $label         = 'label';
        $value         = 'value';
        $options       = json_encode([[
            'label'       => $label,
            'value'       => $value,
        ]]);

        $serializer  = $this->createMock(SerializerInterface::class);
        $model       = $this->createMock(CustomFieldModel::class);

        $transformer = new OptionsToStringTransformer($serializer, $model);
        $options     = $transformer->reverseTransform($options);
        $this->assertInstanceOf(ArrayCollection::class, $options);
        $option = $options->first();
        $this->assertSame($label, $option->getLabel());
        $this->assertSame($value, $option->getValue());
    }
}
