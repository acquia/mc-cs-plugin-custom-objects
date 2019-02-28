<?php

/*
* @copyright   2019 Mautic, Inc. All rights reserved
* @author      Mautic, Inc.
*
* @link        https://mautic.com
*
* @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
*/

namespace MauticPlugin\CustomObjectsBundle\CustomFieldType\Form\Type;

use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class DateTimeType extends \Symfony\Component\Form\Extension\Core\Type\DateTimeType
{
    /**
     * @param OptionsResolverInterface $resolver
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver): void
    {
        $resolver->setDefaults(
            [
                'attr'       => [
                    'class'       => 'form-control',
                    // @todo does not work
                    // @see 1a.content.js:301
                    'data-toggle' => 'datetime',
                ],
                'format'   => 'yyyy-MM-dd HH:mm',
                'required' => false,
            ]
        );

        parent::setDefaultOptions($resolver);
    }
}