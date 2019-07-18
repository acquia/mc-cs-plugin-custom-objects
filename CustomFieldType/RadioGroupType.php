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

class RadioGroupType extends SelectType
{
    /**
     * @var string
     */
    public const NAME = 'custom.field.type.radio_group';

    /**
     * @var string
     */
    protected $key = 'radio_group';

    /**
     * {@inheritdoc}
     */
    protected $formTypeOptions = [
        'expanded'    => true,
        'multiple'    => false,
    ];

    /**
     * {@inheritdoc}
     */
    public function usePlaceholder(): bool
    {
        return false;
    }
}
