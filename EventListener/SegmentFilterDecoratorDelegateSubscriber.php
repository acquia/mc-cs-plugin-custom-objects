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
use MauticPlugin\CustomObjectsBundle\Segment\Decorator\CountryDecorator;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class SegmentFilterDecoratorDelegateSubscriber implements EventSubscriberInterface
{
    /**
     * @var CountryDecorator
     */
    private $countryDecorator;

    public function __construct(CountryDecorator $countryDecorator)
    {
        $this->countryDecorator = $countryDecorator;
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
                case 'country':
                    $delegateEvent->setDecorator($this->countryDecorator);
                    $delegateEvent->stopPropagation();
                    break;
            }
        }
    }
}
