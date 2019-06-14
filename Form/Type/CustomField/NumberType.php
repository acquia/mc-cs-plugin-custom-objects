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

namespace MauticPlugin\CustomObjectsBundle\Form\Type\CustomField;

use Symfony\Component\Form\Extension\Core\Type\NumberType as SymfonyNumberType;
use Symfony\Component\Form\FormBuilderInterface;

class NumberType extends SymfonyNumberType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        // Hook to parent method ignoring transformer which does not allow empty value
        // throwing exception in vendor/symfony/form/Extension/Core/DataTransformer/NumberToLocalizedStringTransformer.php:166
        // because value is null
    }
}
