<?php

declare(strict_types=1);

namespace MauticPlugin\CustomObjectsBundle\Form\Type;

use MauticPlugin\CustomObjectsBundle\Provider\CustomItemRouteProvider;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Translation\TranslatorInterface;

class CampaignActionLinkType extends AbstractType
{
    /**
     * @var CustomItemRouteProvider
     */
    protected $routeProvider;

    /**
     * @var TranslatorInterface
     */
    protected $translator;

    public function __construct(CustomItemRouteProvider $routeProvider, TranslatorInterface $translator)
    {
        $this->routeProvider = $routeProvider;
        $this->translator    = $translator;
    }

    /**
     * @param mixed[] $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add(
            'linkCustomItemName',
            TextType::class,
            [
                'required' => false,
                'label'    => 'custom.item.link.contact',
                'attr'     => [
                    'tooltip'                => 'custom.item.link.contact.descr',
                    'data-toggle'            => 'typeahead',
                    'data-id-input-selector' => '.link-custom-item-id',
                    'data-action'            => $this->routeProvider->buildLookupRoute((int) $options['customObjectId']),
                    'data-selected-message'  => $this->translator->trans('custom.item.selected'),
                    'data-custom-object-id'  => $options['customObjectId'],
                    'class'                  => 'form-control',
                ],
            ]
        );

        $builder->add(
            'linkCustomItemId',
            HiddenType::class,
            [
                'required' => false,
                'attr'     => ['class' => 'link-custom-item-id'],
            ]
        );

        $builder->add(
            'unlinkCustomItemName',
            TextType::class,
            [
                'required' => false,
                'label'    => 'custom.item.unlink.contact',
                'attr'     => [
                    'tooltip'                => 'custom.item.unlink.contact.descr',
                    'data-toggle'            => 'typeahead',
                    'data-id-input-selector' => '.unlink-custom-item-id',
                    'data-action'            => $this->routeProvider->buildLookupRoute((int) $options['customObjectId']),
                    'data-selected-message'  => $this->translator->trans('custom.item.selected'),
                    'data-custom-object-id'  => $options['customObjectId'],
                    'class'                  => 'form-control',
                ],
            ]
        );

        $builder->add(
            'unlinkCustomItemId',
            HiddenType::class,
            [
                'required' => false,
                'attr'     => ['class' => 'unlink-custom-item-id'],
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setRequired(['customObjectId']);
    }
}
