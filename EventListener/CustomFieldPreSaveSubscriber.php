<?php

declare(strict_types=1);

/*
* @copyright   2019 Mautic, Inc. All rights reserved
* @author      Mautic, Inc.
*
* @link        https://mautic.com
*
* @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
*/

namespace MauticPlugin\CustomObjectsBundle\EventListener;

use MauticPlugin\CustomObjectsBundle\CustomObjectEvents;
use MauticPlugin\CustomObjectsBundle\Entity\CustomField;
use MauticPlugin\CustomObjectsBundle\Event\CustomObjectEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * CustomField entity lifecycle ends here.
 */
class CustomFieldPreSaveSubscriber implements EventSubscriberInterface
{
    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        // Doctrine event preFlush could not be used,
        // because it does not contain entity itself
        return [
            CustomObjectEvents::ON_CUSTOM_OBJECT_PRE_SAVE => 'preSave',
        ];
    }

    /**
     * @param CustomObjectEvent $event
     */
    public function preSave(CustomObjectEvent $event): void
    {
        $customObject = $event->getCustomObject();

        /** @var CustomField $customField */
        foreach ($customObject->getCustomFields() as $customField) {
            $customField->setParams($customField->getParams()->__toArray());
        }
    }
}
