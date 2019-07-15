<?php

declare(strict_types=1);

/*
 * @copyright   2019 Mautic Contributors. All rights reserved
 * @author      Mautic Inc.
 *
 * @link        https://mautic.com
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\CustomObjectsBundle\EventListener;

use Mautic\LeadBundle\Event\LeadListFiltersDecoratorDelegateEvent;
use Mautic\LeadBundle\LeadEvents;
use MauticPlugin\CustomObjectsBundle\Segment\Decorator\MultiselectDecorator;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class SegmentFilterDecoratorDelegateSubscriber implements EventSubscriberInterface
{
    /**
     * @var MultiselectDecorator
     */
    private $multiselectDecorator;

    /**
     * @param MultiselectDecorator $multiselectDecorator
     */
    public function __construct(MultiselectDecorator $multiselectDecorator)
    {
        $this->multiselectDecorator = $multiselectDecorator;
    }

    /**
     * @return mixed[]
     */
    public static function getSubscribedEvents(): array
    {
        return [
            LeadEvents::SEGMENT_ON_DECORATOR_DELEGATE => 'onDecoratorDelegate',
        ];
    }

    /**
     * @param LeadListFiltersDecoratorDelegateEvent $delegateEvent
     */
    public function onDecoratorDelegate(LeadListFiltersDecoratorDelegateEvent $delegateEvent): void {
        $crate = $delegateEvent->getCrate();

        if ($crate->getObject()==='custom_object') {
            switch($crate->getType()) {
                case 'multiselect':
                    $delegateEvent->setDecorator($this->multiselectDecorator);
                    $delegateEvent->stopPropagation();
                    break;
            }
        }
    }
}
