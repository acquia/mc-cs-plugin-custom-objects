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

use MauticPlugin\CustomObjectsBundle\Exception\NotFoundException;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use MauticPlugin\CustomObjectsBundle\Entity\CustomFieldValueInterface;
use MauticPlugin\CustomObjectsBundle\Entity\CustomItem;
use MauticPlugin\CustomObjectsBundle\Exception\UndefinedTransformerException;

class CustomFieldValueType extends AbstractType
{
    /**
     * @param FormBuilderInterface $builder
     * @param mixed[]              $options
     *
     * @throws NotFoundException
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        /** @var CustomItem $customItem */
        $customItem       = $options['customItem'];
        $customFieldId    = (int) $builder->getName(); // @todo Check Symfony 3 compatibility
        $customFieldValue = $customItem->findCustomFieldValueForFieldId($customFieldId);
        $customField      = $customFieldValue->getCustomField();
        $symfonyFormType  = $customField->getTypeObject()->getSymfonyFormFieldType();
        $options          = $customItem->getId() ? [] : ['data' => $customField->getDefaultValue()];
        $options          = $customField->getFormFieldOptions($options);
        $formField        = $builder->create('value', $symfonyFormType, $options);

        try {
            $viewTransformer = $customField->getTypeObject()->createViewTransformer();
            $formField->addViewTransformer($viewTransformer);
        } catch (UndefinedTransformerException $e) {
        }

        $builder->add($formField);
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => CustomFieldValueInterface::class]);
        $resolver->setRequired(['customItem']);
    }
}
