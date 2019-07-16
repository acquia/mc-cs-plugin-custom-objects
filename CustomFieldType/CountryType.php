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
    /** @var array */
    private $countryList;

    /**
     * @var string
     */
    public const NAME = 'custom.field.type.country';

    /**
     * @var string
     */
    protected $key = 'country';

    /**
     * {@inheritdoc}
     */
    public function getChoices()
    {
        if (is_null($this->countryList)) {
            $this->countryList = array_flip(FormFieldHelper::getCountryChoices());
        }

        return ['choices' => $this->countryList];
    }
}