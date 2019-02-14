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

use Symfony\Component\Form\Extension\Core\Type\TextType;

class PhoneType extends AbstractTextType
{
    /**
     * @var string
     */
    protected $key = 'phone';

    /**
     * @return string
     */
    public function getTableAlias(): string
    {
        return 'cfvphone';
    }
}