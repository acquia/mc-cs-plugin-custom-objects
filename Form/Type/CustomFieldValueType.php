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

use MauticPlugin\CustomObjectsBundle\CustomFieldType\DataTransformer\ViewDateTransformer;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use MauticPlugin\CustomObjectsBundle\Entity\CustomFieldValueInterface;
use MauticPlugin\CustomObjectsBundle\Entity\CustomItem;

class CustomFieldValueType extends AbstractType
{
    /**
     * @param FormBuilderInterface $builder
     * @param mixed[]              $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        /** @var CustomItem $customItem */
        $customItem       = $options['customItem'];
        $customFieldId    = (int) $builder->getName();
        $customFieldValue = $customItem->findCustomFieldValueForFieldId($customFieldId);
        $customField      = $customFieldValue->getCustomField();
        $options          = ['empty_data'  => $customItem->getId() ? null : $customField->getDefaultValue()];
        $symfonyFormType  =  $customField->getTypeObject()->getSymfonyFormFieldType();

        if (DateType::class === $symfonyFormType || DateTimeType::class === $symfonyFormType) {
            $builder->add(
                $builder->create(
                    'value',
                    $customField->getTypeObject()->getSymfonyFormFieldType(),
                    $customField->getFormFieldOptions($options)
                )->addViewTransformer(new ViewDateTransformer())
            );
        } else {
            $builder->add(
                'value',
                $customField->getTypeObject()->getSymfonyFormFieldType(),
                $customField->getFormFieldOptions($options)
            );
        }
    }

    /**
     * {@inheritdoc}
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver): void
    {
        $resolver->setDefaults(['data_class' => CustomFieldValueInterface::class]);
        $resolver->setRequired(['customItem']);
    }
}
