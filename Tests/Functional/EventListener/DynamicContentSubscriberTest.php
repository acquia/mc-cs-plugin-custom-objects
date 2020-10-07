<?php

declare(strict_types=1);

namespace MauticPlugin\CustomObjectsBundle\Tests\Functional\EventListener;

use Mautic\DynamicContentBundle\DynamicContentEvents;
use MauticPlugin\CustomObjectsBundle\EventListener\DynamicContentSubscriber;
use PHPUnit\Framework\TestCase;

class DynamicContentSubscriberTest extends TestCase
{
    public function testSubscribesToEvent(): void
    {
        $eventSubscriptions = DynamicContentSubscriber::getSubscribedEvents();
        $methodName         = $eventSubscriptions[DynamicContentEvents::ON_CONTACTS_FILTER_EVALUATE][0];

        $this->assertSame('evaluateFilters', $methodName);
    }
}
