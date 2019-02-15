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

namespace MauticPlugin\CustomObjectsBundle\CustomFieldType;

use Symfony\Component\Form\FormBuilderInterface;

class TextareaType extends AbstractTextType
{
    /**
     * @var string
     */
    protected $key = 'textarea';

    /**
     * @return string
     */
    public function getSymfonyFormFiledType(): string
    {
        return \Symfony\Component\Form\Extension\Core\Type\TextareaType::class;
    }

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
            \Symfony\Component\Form\Extension\Core\Type\TextareaType::class
        )->get($name);
    }
}