<?php

/*
 * @copyright   2019 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\CustomObjectsBundle\CustomFieldType;

use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;

class SelectType extends AbstractTextType
{
    /**
     * @var string
     */
    protected $key = 'select';

    /**
     * @param FormBuilderInterface $builder
     * @param string               $name
     *
     * @return FormBuilderInterface
     */
    public function createSymfonyFormFiledType(FormBuilderInterface $builder, string $name): FormBuilderInterface
    {
        return $builder->add(
            $name,
            ChoiceType::class,
            [
                'expanded' => false,
                'multiple' => false,
            ]
        )->get($name);
    }

    /**
     * @return string
     */
    public function getTableAlias(): string
    {
        return 'cfvselect';
    }
}