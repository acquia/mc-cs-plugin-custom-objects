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

class SelectType extends AbstractTextType
{
    protected $key = 'select';

    /**
     * @return string
     */
    public function getSymfonyFormFiledType(): string
    {
        return ChoiceType::class;
    }

    /**
     * @return string
     */
    public function getTableAlias(): string
    {
        return 'cfvselect';
    }
}