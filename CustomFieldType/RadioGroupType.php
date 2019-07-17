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

class RadioGroupType extends AbstractTextType
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
        'multiple'    => true,
    ];

    /**
     * @return mixed[]
     */
    public function getOperators(): array
    {
        $allOperators     = parent::getOperators();
        $allowedOperators = array_flip(['in', '!in', 'empty', '!empty']);

        return array_intersect_key($allOperators, $allowedOperators);
    }

    /**
     * @return string
     */
    public function getSymfonyFormFieldType(): string
    {
        return ChoiceType::class;
    }
}


