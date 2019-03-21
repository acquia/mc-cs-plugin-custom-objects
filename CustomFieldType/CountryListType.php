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

use Symfony\Component\Form\Extension\Core\Type\CountryType;

class CountryListType extends SelectType
{
    /**
     * @var string
     */
    public const NAME = 'custom.field.type.country_list';

    /**
     * @var string
     */
    protected $key = 'country_list';

    /**
     * @return string
     */
    public function getSymfonyFormFieldType(): string
    {
        return CountryType::class;
    }
}
