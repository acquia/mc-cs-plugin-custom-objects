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

use Symfony\Component\Form\FormBuilderInterface;

class CheckboxGroupType extends AbstractTextType
{
    /**
     * @var string
     */
    protected $key = 'checkbox_group';

    /**
     * @return FormBuilderInterface
     */
    public function getSymfonyFormFiledType(): string
    {
        return MauticPlugin\CustomObjectsBundle\CustomFieldType\Form\Type\CheckboxGroupType::class;
    }
}