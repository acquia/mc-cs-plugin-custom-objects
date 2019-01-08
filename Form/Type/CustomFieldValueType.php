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
use MauticPlugin\CustomObjectsBundle\Exception\NotFoundException;
use MauticPlugin\CustomObjectsBundle\Entity\CustomFieldValueText;
use MauticPlugin\CustomObjectsBundle\Provider\CustomFieldTypeProvider;
use MauticPlugin\CustomObjectsBundle\Entity\CustomFieldValueInterface;

class CustomFieldValueType extends AbstractType
{
    /**
     * @var CustomFieldTypeProvider
     */
    private $customFieldTypeProvider;

    /**
     * @param CustomFieldTypeProvider $customFieldTypeProvider
     */
    public function __construct(CustomFieldTypeProvider $customFieldTypeProvider)
    {
        $this->customFieldTypeProvider = $customFieldTypeProvider;
    }

    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $collection       = $options['entityCollection'];
        $customFieldId    = (int) $builder->getName();
        $customFieldValue = $collection->get($customFieldId);
        $customField      = $customFieldValue->getCustomField();
        $fieldType        = $this->customFieldTypeProvider->getType($customField->getType());

        $builder->add(
            'value',
            $fieldType->getSymfonyFormFiledType(),
            [
                'label'      => $customFieldValue->getCustomField()->getLabel(),
                'required'   => true, // make this dynamic
                'label_attr' => ['class' => 'control-label'],
                'attr'       => ['class' => 'form-control'],
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array('data_class' => CustomFieldValueInterface::class,));
        $resolver->setRequired(['entityCollection']);
    }
}
