<?php

declare(strict_types=1);

namespace MauticPlugin\CustomObjectsBundle\CustomFieldType;

use Mautic\LeadBundle\Helper\FormFieldHelper;
use Symfony\Component\Form\Extension\Core\Type\CountryType as SymfonyCountryType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CountryType extends SelectType implements StaticChoiceTypeInterface
{
    /**
     * @var string
     */
    public const NAME = 'custom.field.type.country';

    /**
     * @var string
     */
    protected $key = 'country';

    /**
     * @var string[]
     */
    private $countryList;

    /**
     * {@inheritdoc}
     */
    public function getChoices(): array
    {
        if (null === $this->countryList) {
            $this->countryList = array_flip(FormFieldHelper::getCountryChoices());
        }

        return $this->countryList;
    }

    public function getSymfonyFormFieldType(): string
    {
        return SymfonyCountryType::class;
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'choices'                   => ['choices' => $this->getChoices()],
            'choice_translation_domain' => false,
        ]);
    }
}
