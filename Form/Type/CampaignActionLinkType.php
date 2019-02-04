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

namespace MauticPlugin\CustomObjectsBundle\Form\Type;

use MauticPlugin\CustomObjectsBundle\Form\CustomObjectHiddenTransformer;
use MauticPlugin\CustomObjectsBundle\Repository\CustomObjectRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use MauticPlugin\CustomObjectsBundle\Entity\CustomField;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Mautic\CoreBundle\Form\Type\FormButtonsType;
use Symfony\Component\Validator\Constraints\NotBlank;
use MauticPlugin\CustomObjectsBundle\Provider\CustomItemRouteProvider;
use Symfony\Component\Translation\TranslatorInterface;

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

    /**
     * @param CustomItemRouteProvider $router
     * @param TranslatorInterface $translator
     */
    public function __construct(CustomItemRouteProvider $routeProvider, TranslatorInterface $translator)
    {
        $this->routeProvider = $routeProvider;
        $this->translator = $translator;
    }

    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
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
                    'data-action'            => 'route provider here',
                    'data-id-input-selector' => '.link-custom-item-id',
                    'data-action'            => $this->routeProvider->buildLookupRoute($options['customObjectId']),
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
                    'data-action'            => 'route provider here',
                    'data-id-input-selector' => '.unlink-custom-item-id',
                    'data-action'            => $this->routeProvider->buildLookupRoute($options['customObjectId']),
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
    public function setDefaultOptions(OptionsResolverInterface $resolver): void
    {
        $resolver->setRequired(['customObjectId']);
    }
}
