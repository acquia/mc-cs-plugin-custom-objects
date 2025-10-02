<?php

declare(strict_types=1);

namespace MauticPlugin\CustomObjectsBundle\Tests\Unit\CustomFieldType;

use Mautic\LeadBundle\Provider\FilterOperatorProviderInterface;
use MauticPlugin\CustomObjectsBundle\CustomFieldType\CountryType;
use MauticPlugin\CustomObjectsBundle\Entity\CustomFieldValueText;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Translation\TranslatorInterface;

class CountryTypeTest extends \PHPUnit\Framework\TestCase
{
    private $translator;

    /**
     * @var CountryType
     */
    private $fieldType;

    protected function setUp(): void
    {
        parent::setUp();

        $this->translator = $this->createMock(TranslatorInterface::class);
        $this->fieldType  = new CountryType(
            $this->translator,
            $this->createMock(FilterOperatorProviderInterface::class)
        );
    }

    public function testGetSymfonyFormFieldType(): void
    {
        $this->assertSame(
            \Symfony\Component\Form\Extension\Core\Type\CountryType::class,
            $this->fieldType->getSymfonyFormFieldType()
        );
    }

    public function testGetEntityClass(): void
    {
        $this->assertSame(
            CustomFieldValueText::class,
            $this->fieldType->getEntityClass()
        );
    }

    public function testConfigureOptions(): void
    {
        $optionsResolver = $this->createMock(OptionsResolver::class);

        $optionsResolver->expects($this->once())
            ->method('setDefaults')
            ->with($this->callback(function (array $options) {
                $this->assertFalse($options['choice_translation_domain']);
                $this->assertSame('Czech Republic', $options['choices']['choices']['Czech Republic']);

                return true;
            }));

        $this->fieldType->configureOptions($optionsResolver);
    }
}
