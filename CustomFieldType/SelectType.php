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

class SelectType extends AbstractTextType
{
    /**
     * @var string
     */
    protected $key = 'select';

    /**
     * {@inheritdoc}
     */
    protected $formTypeOptions = [
        'expanded' => false,
        'multiple' => false,
    ];

    /**
     * @return string
     */
    public function getSymfonyFormFieldType(): string
    {
        return ChoiceType::class;
    }

    /**
     * @return mixed[]
     */
    public function getOperators(): array
    {
        $allOperators     = parent::getOperators();
        $allowedOperators = array_flip(['=', '!=', 'empty', '!empty']);

        return array_intersect_key($allOperators, $allowedOperators);
    }
}
