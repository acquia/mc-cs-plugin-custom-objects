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
use MauticPlugin\CustomObjectsBundle\CustomFieldType\CustomFieldTypeInterface;
use MauticPlugin\CustomObjectsBundle\Exception\NotFoundException;

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

    /**
     * @param string $key
     * 
     * @return CustomFieldTypeInterface
     * 
     * @throws NotFoundException
     */
    public function getType(string $key): CustomFieldTypeInterface
    {
        $this->getTypes();
        
        if (isset($this->customFieldTypes[$key])) {
            return $this->customFieldTypes[$key];
        }

        throw new NotFoundException("Field type '{$key}' does not exist.");
    }
}
