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

class HiddenType extends AbstractTextType
{
    protected $key = 'hidden';

    /**
     * @return string
     */
    public function getSymfonyFormFiledType(): string
    {
        return \Symfony\Component\Form\Extension\Core\Type\HiddenType::class;
    }

    /**
     * @return string
     */
    public function getTableAlias(): string
    {
        return 'cfvhidden';
    }
}