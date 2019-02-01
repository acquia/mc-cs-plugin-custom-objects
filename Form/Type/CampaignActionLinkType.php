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
use Symfony\Component\Form\Extension\Core\Type\NumberType;

class CampaignActionLinkType extends AbstractType
{
    /**
     * @var CustomItemRouteProvider
     */
    protected $routeProvider;

    /**
     * @param CustomItemRouteProvider $router
     */
    public function __construct(CustomItemRouteProvider $routeProvider)
    {
        $this->routeProvider = $routeProvider;
    }

    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add(
            'customItemName',
            TextType::class,
            [
                'required'    => true,
                'constraints' => [new NotBlank(['message' => 'custom.item.choose.notblank'])],
                'attr'        => [
                    'data-toggle' => 'typeahead',
                    'data-action' => 'route provider here',
                    'class'       => 'form-control',
                    'data-action' => $this->routeProvider->buildLookupRoute($options['customObjectId']),
                    'onfocus'     => "CustomObjects.initTypeaheadOnFocus(this, {$options['customObjectId']})",
                ],
            ]
        );

        $builder->add(
            'customItemId',
            NumberType::class,
            [
                'required'    => true,
                'constraints' => [new NotBlank(['message' => 'custom.item.choose.notblank'])],
                'attr'        => [
                    'readonly' => 'typeahead',
                    'class'    => 'form-control',
                ],
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
