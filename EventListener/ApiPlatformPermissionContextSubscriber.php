<?php

declare(strict_types=1);

namespace MauticPlugin\CustomObjectsBundle\EventListener;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Request;

final class ApiPlatformPermissionContextSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            'mautic.api_platform_permission_context' => ['onApiPlatformPermissionContext', 0],
        ];
    }

    /*
     * Note, the new ApiPlatformPermissionContextEvent will be available in Mautic 7.
     * Till then we have to go with a plain object type.
     */
    public function onApiPlatformPermissionContext(object $event): void
    {
        if (!method_exists($event, 'getPermission')
            || !method_exists($event, 'setPermission')
            || !method_exists($event, 'getRequestObject')
            || !method_exists($event, 'setRequestObject')
        ) {
            return;
        }

        $permission = $event->getPermission();
        if (!is_string($permission) || 0 !== strpos($permission, 'custom_objects:')) {
            return;
        }

        if (false === strpos($permission, '[') && false === strpos($permission, '(')) {
            return;
        }

        $requestObject = $event->getRequestObject();
        $objectPath    = $this->extractObjectPath($permission);

        if (null !== $objectPath) {
            $requestObject = $this->resolveRequestObject($requestObject, $objectPath);
            $permission    = substr($permission, 0, strpos($permission, '('));
        }

        $event->setRequestObject($requestObject);
        $event->setPermission($this->resolvePermissionPlaceholder($event, $requestObject, $permission));
    }

    private function extractObjectPath(string $permission): ?string
    {
        if (1 !== preg_match('#\((.*?)\)#', $permission, $match)) {
            return null;
        }

        if (!isset($match[1]) || '' === $match[1]) {
            return null;
        }

        return $match[1];
    }

    private function resolveRequestObject(mixed $requestObject, string $objectPath): mixed
    {
        foreach (explode('.', $objectPath) as $property) {
            if (!is_object($requestObject) || !method_exists($requestObject, $property)) {
                return $requestObject;
            }

            $requestObject = $requestObject->$property();
        }

        return $requestObject;
    }

    private function resolvePermissionPlaceholder(object $event, mixed $requestObject, string $permission): string
    {
        if (1 !== preg_match('#\[(.*?)\]#', $permission, $match)) {
            return $permission;
        }

        if (!isset($match[1]) || '' === $match[1]) {
            return $permission;
        }

        $property = $match[1];
        $objectId = null;
        $request  = method_exists($event, 'getRequest') ? $event->getRequest() : null;
        $content  = $request instanceof Request ? json_decode($request->getContent(), true) : null;

        if (is_array($content) && array_key_exists($property, $content)) {
            $objectId = $content[$property];
        } elseif (is_object($requestObject)) {
            $getter = 'get'.ucfirst($property);
            if (method_exists($requestObject, $getter)) {
                $objectId = $requestObject->$getter();
            }
        }

        if (is_object($objectId) && method_exists($objectId, 'getId')) {
            $objectId = $objectId->getId();
        }

        if (is_string($objectId) && false !== strrpos($objectId, '/')) {
            $objectId = substr($objectId, strrpos($objectId, '/') + 1);
        }

        if (null === $objectId || '' === $objectId) {
            return $permission;
        }

        return preg_replace('#\[(.*?)\]#', (string) $objectId, $permission, 1) ?? $permission;
    }
}
