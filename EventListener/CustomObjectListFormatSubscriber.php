<?php

declare(strict_types=1);

namespace MauticPlugin\CustomObjectsBundle\EventListener;

use MauticPlugin\CustomObjectsBundle\CustomObjectEvents;
use MauticPlugin\CustomObjectsBundle\Event\CustomObjectListFormatEvent;
use MauticPlugin\CustomObjectsBundle\Helper\TokenFormatter;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class CustomObjectListFormatSubscriber implements EventSubscriberInterface
{
    /**
     * @var TokenFormatter
     */
    private $tokenFormatter;

    public function __construct(TokenFormatter $tokenFormatter)
    {
        $this->tokenFormatter = $tokenFormatter;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            CustomObjectEvents::ON_CUSTOM_OBJECT_LIST_FORMAT => ['onFormatList', 0],
        ];
    }

    public function onFormatList(CustomObjectListFormatEvent $event): void
    {
        $format = $event->getFormat();
        if ($this->tokenFormatter->isValidFormat($format)) {
            $values = $event->getCustomObjectValues();
            $event->setFormattedString($this->tokenFormatter->format($values, $format));
        }
    }
}
