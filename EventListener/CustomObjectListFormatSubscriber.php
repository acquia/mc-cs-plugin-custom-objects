<?php

declare(strict_types=1);

namespace MauticPlugin\CustomObjectsBundle\EventListener;

use MauticPlugin\CustomObjectsBundle\CustomObjectEvents;
use MauticPlugin\CustomObjectsBundle\Event\CustomObjectListFormatEvent;
use MauticPlugin\CustomObjectsBundle\Helper\CustomObjectTokenFormatter;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class CustomObjectListFormatSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            CustomObjectEvents::ON_CUSTOM_OBJECT_LIST_FORMAT => 'formatCustomObjectsList',
        ];
    }

    public function formatCustomObjectsList(CustomObjectListFormatEvent $event): void
    {
        $format = $event->getFormat();
        if (CustomObjectTokenFormatter::isValidFormat($format)) {
            $values = $event->getCustomObjectValues();
            $event->setFormattedString(CustomObjectTokenFormatter::format($values, $format));
        }
    }
}
