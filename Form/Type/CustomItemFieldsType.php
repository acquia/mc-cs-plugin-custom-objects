<?php

declare(strict_types=1);

/*
 * @copyright   2018 Mautic, Inc. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.com
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\CustomObjectsBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use MauticPlugin\CustomObjectsBundle\Model\CustomFieldModel;

class CustomItemFieldsType extends AbstractType
{
    /**
     * @var CustomFieldModel
     */
    private $customFieldModel;

    /**
     * @param CustomFieldModel $customFieldModel
     */
    public function __construct(CustomFieldModel $customFieldModel)
    {
        $this->customFieldModel = $customFieldModel;
    }

    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $customFields = $this->customFieldModel->fetchEntities([
            'filter' => [
                'force' => [
                    [
                        'column' => 'e.customObject',
                        'value'  => $options['objectId'],
                        'expr'   => 'eq',
                    ],
                ],
            ],
        ]);

        foreach ($customFields as $customField) 
        {
            $builder->add(
                $customField->getId(),
                $customField->getType(),
                [
                    'label'      => $customField->getLabel(),
                    'required'   => false, // make this dynamic
                    'label_attr' => ['class' => 'control-label'],
                    'attr'       => ['class' => 'form-control'],
                    'data'       => $options['customFieldValues'][$customField->getId()],
                ]
            );
        }
    }

    /**
     * {@inheritdoc}
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setRequired(['objectId']);
        $resolver->setOptional(['customFieldValues']);
    }
}
