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

namespace MauticPlugin\CustomObjectsBundle\CustomFieldType\Form\Type;

use Symfony\Component\OptionsResolver\OptionsResolver;

class DateType extends \Symfony\Component\Form\Extension\Core\Type\DateTimeType
{
    /**
     * Configures the options for this type.
     *
     * @param OptionsResolver $resolver The resolver for the options
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        parent::configureOptions($resolver);

        $resolver->setDefaults(
            [
                'widget'     => 'single_text',
                'attr'       => [
                    'class' => 'form-control',
                    // @todo does not work, overwriten by definition
                    // @see 1a.content.js:301
                    'data-toggle' => 'date',
                ],
                'format'   => 'yyyy-MM-dd',
                'required' => false,
            ]
        );
    }
}
