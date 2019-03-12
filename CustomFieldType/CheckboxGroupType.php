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

use Symfony\Component\Form\Extension\Core\Type\ChoiceType;

class CheckboxGroupType extends AbstractTextType
{
    /**
     * @var string
     */
    protected $key = 'checkbox_group';

    /**
     * {@inheritdoc}
     */
    protected $formTypeOptions = [
        'expanded' => true,
        'multiple' => true,
    ];

    /**
     * @return string
     */
    public function getSymfonyFormFieldType(): string
    {
        return ChoiceType::class;
    }
}
