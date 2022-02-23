<?php

declare(strict_types=1);

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

    public function onDecoratorDelegate(LeadListFiltersDecoratorDelegateEvent $delegateEvent): void
    {
        $crate = $delegateEvent->getCrate();

        if ('custom_object' === $crate->getObject()) {
            switch ($crate->getType()) {
                case 'multiselect':
                    $delegateEvent->setDecorator($this->multiselectDecorator);
                    $delegateEvent->stopPropagation();

                    break;
            }
        }
    }
}
