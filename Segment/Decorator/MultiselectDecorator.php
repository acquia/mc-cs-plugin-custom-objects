<?php

declare(strict_types=1);

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
