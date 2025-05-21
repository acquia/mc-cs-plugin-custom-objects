<?php

namespace MauticPlugin\CustomObjectsBundle\Polyfill\EventListener;

use Mautic\LeadBundle\Entity\LeadListRepository;

/**
 * Polyfill for \Mautic\EmailBundle\EventListener\MatchFilterForLeadTrait.
 */
trait MatchFilterForLeadTrait
{
    use \Mautic\EmailBundle\EventListener\MatchFilterForLeadTrait;

    private LeadListRepository $segmentRepository;

    /**
     * @param mixed[] $data
     * @param mixed[] $lead
     *
     * @return ?mixed[]
     */
    private function transformFilterDataForLead(array $data, array $lead): ?array
    {
        return null;
    }
}
