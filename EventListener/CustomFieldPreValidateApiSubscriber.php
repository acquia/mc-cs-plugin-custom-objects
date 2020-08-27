<?php

declare(strict_types=1);

namespace MauticPlugin\CustomObjectsBundle\EventListener;


use ApiPlatform\Core\EventListener\EventPriorities;
use ApiPlatform\Core\Exception\InvalidArgumentException;
use MauticPlugin\CustomObjectsBundle\Entity\CustomField;
use MauticPlugin\CustomObjectsBundle\Exception\NotFoundException;
use MauticPlugin\CustomObjectsBundle\Provider\CustomFieldTypeProvider;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\GetResponseForControllerResultEvent;
use Symfony\Component\HttpKernel\KernelEvents;

final class CustomFieldPreValidateApiSubscriber implements EventSubscriberInterface
{
    private $customFieldTypeProvider;

    public function __construct(CustomFieldTypeProvider $customFieldTypeProvider)
    {
        $this->customFieldTypeProvider = $customFieldTypeProvider;
    }

    public static function getSubscribedEvents()
    {
        return [
            KernelEvents::VIEW => ['addType', EventPriorities::PRE_VALIDATE],
        ];
    }

    public function addType(GetResponseForControllerResultEvent $event): void
    {
        $entity = $event->getControllerResult();
        $method = $event->getRequest()->getMethod();

        if (!$entity instanceof CustomField ||
            (
                Request::METHOD_POST !== $method &&
                Request::METHOD_PATCH !== $method &&
                Request::METHOD_PUT !== $method
            )
        ) {
            return;
        }

        $type = $entity->getType();
        try {
            $entity->setTypeObject($this->customFieldTypeProvider->getType($type));
        }
        catch(NotFoundException $e) {
            throw new InvalidArgumentException($e->getMessage());
        }
    }
}
