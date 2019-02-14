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

use Mautic\FormBundle\Form\Type\FormFieldHTMLType;
use Symfony\Component\Form\FormBuilderInterface;

class HtmlAreaType extends AbstractTextType
{
    /**
     * @var string
     */
    protected $key = 'hidden';

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
            FormFieldHTMLType::class
        )->get($name);
    }

    /**
     * @return string
     */
    public function getTableAlias(): string
    {
        return 'cfvhtmlarea';
    }
}