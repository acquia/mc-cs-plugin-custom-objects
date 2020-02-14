<?php

declare(strict_types=1);

/*
 * @copyright   2018 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\CustomObjectsBundle\Segment\Decorator;

use Mautic\LeadBundle\Segment\ContactSegmentFilterCrate;
use Mautic\LeadBundle\Segment\Decorator\CustomMappedDecorator;

class MultiselectDecorator extends CustomMappedDecorator
{
    /**
     * @return string[]
     */
    public function getParameterValue(ContactSegmentFilterCrate $contactSegmentFilterCrate): array
    {
        return $contactSegmentFilterCrate->getFilter() ?? [];
    }
}
