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

use Mautic\LeadBundle\Helper\FormFieldHelper;

class CountryType extends SelectType
{
    /**
     * @var string
     */
    public const NAME = 'custom.field.type.country';

    /**
     * @var string
     */
    protected $key = 'country';

    /**
     * @var string[]
     */
    private $countryList;

    /**
     * {@inheritdoc}
     */
    public function getChoices(): array
    {
        if (null === $this->countryList) {
            $this->countryList = array_flip(FormFieldHelper::getCountryChoices());
        }

        return ['choices' => $this->countryList];
    }
}
