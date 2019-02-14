<?php

/*
 * @copyright   2019 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\CustomObjectsBundle\CustomFieldType;

class CountryListType extends SelectType
{
    /**
     * @var string
     */
    protected $key = 'countrylist';

    /**
     * @return string
     */
    public function getTableAlias(): string
    {
        return 'cfvcountrylist';
    }
}