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

namespace MauticPlugin\CustomObjectsBundle\Tests\Unit\CustomFieldType;

use Symfony\Component\Translation\TranslatorInterface;
use MauticPlugin\CustomObjectsBundle\CustomFieldType\CountryType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use MauticPlugin\CustomObjectsBundle\Entity\CustomFieldValueText;

class CountryTypeTest extends \PHPUnit_Framework_TestCase
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
        $this->fieldType  = new CountryType($this->translator);
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
                $this->assertTrue($options['choices_as_values']);
                $this->assertFalse($options['choice_translation_domain']);
                $this->assertSame('Czech Republic', $options['choices']['choices']['Czech Republic']);

                return true;
            }));

        $this->fieldType->configureOptions($optionsResolver);
    }
}
