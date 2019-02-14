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

class DescriptionAreaType extends AbstractTextType
{
    protected $key = 'descriptionarea';

    /**
     * @return string
     */
    public function getSymfonyFormFiledType(): string
    {
        \Symfony\Component\Form\Extension\Core\Type\TextareaType::class;
    }

    /**
     * @return string
     */
    public function getTableAlias(): string
    {
        return 'cfvdescriptionarea';
    }
}