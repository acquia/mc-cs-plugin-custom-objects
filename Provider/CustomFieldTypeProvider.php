<?php

declare(strict_types=1);

/*
 * @copyright   2018 Mautic, Inc. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.com
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\CustomObjectsBundle\Provider;

use Symfony\Component\EventDispatcher\ContainerAwareEventDispatcher;
use MauticPlugin\CustomObjectsBundle\CustomFieldEvents;
use MauticPlugin\CustomObjectsBundle\Event\CustomFieldTypeEvent;
use Symfony\Component\EventDispatcher\Debug\TraceableEventDispatcherInterface;

class CustomFieldTypeProvider
{
    /**
     * @var TraceableEventDispatcherInterface
     */
    private $dispatcher;

    /**
     * @var array
     */
    private $customFieldTypes = [];

    /**
     * @param TraceableEventDispatcherInterface $dispatcher
     */
    public function __construct(TraceableEventDispatcherInterface $dispatcher)
    {
        $this->dispatcher = $dispatcher;
    }

    /**
     * Builds the list of custom field type objects.
     *
     * @return array
     */
    public function getTypes(): array
    {
        if (empty($this->customFieldTypes)) {
            $event = new CustomFieldTypeEvent();
            $this->dispatcher->dispatch(CustomFieldEvents::MAKE_FIELD_TYPE_LIST, $event);
            $this->customFieldTypes = $event->getCustomFieldTypes();
        }
        
        return $this->customFieldTypes;
    }
}
