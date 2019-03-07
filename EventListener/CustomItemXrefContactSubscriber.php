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

namespace MauticPlugin\CustomObjectsBundle\EventListener;

use Mautic\CoreBundle\EventListener\CommonSubscriber;
use MauticPlugin\CustomObjectsBundle\CustomItemEvents;
use MauticPlugin\CustomObjectsBundle\Event\CustomItemXrefContactEvent;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Entity\LeadEventLog;
use MauticPlugin\CustomObjectsBundle\Entity\CustomItemXrefContact;
use Doctrine\ORM\EntityManager;
use Mautic\CoreBundle\Helper\UserHelper;

class CustomItemXrefContactSubscriber extends CommonSubscriber
{
    /**
     * @var EntityManager
     */
    private $entityManager;

    /**
     * @var UserHelper
     */
    private $userHelper;

    /**
     * @param EntityManager $entityManager
     * @param UserHelper    $userHelper
     */
    public function __construct(
        EntityManager $entityManager,
        UserHelper $userHelper
    ) {
        $this->entityManager = $entityManager;
        $this->userHelper    = $userHelper;
    }

    /**
     * @return mixed[]
     */
    public static function getSubscribedEvents(): array
    {
        return [
            CustomItemEvents::ON_CUSTOM_ITEM_LINK_CONTACT   => 'onLinkedContact',
            CustomItemEvents::ON_CUSTOM_ITEM_UNLINK_CONTACT => 'onUnlinkedContact',
        ];
    }

    /**
     * @param CustomItemXrefContactEvent $event
     */
    public function onLinkedContact(CustomItemXrefContactEvent $event): void
    {
        $this->saveEventLog($event->getXref(), 'link');
    }

    /**
     * @param CustomItemXrefContactEvent $event
     */
    public function onUnlinkedContact(CustomItemXrefContactEvent $event): void
    {
        $this->saveEventLog($event->getXref(), 'unlink');
    }

    /**
     * @param CustomItemXrefContact $xRef
     * @param string                $eventName
     */
    private function saveEventLog(CustomItemXrefContact $xRef, string $eventName): void
    {
        $contact      = $xRef->getContact();
        $customItemId = $xRef->getCustomItem()->getId();
        $eventLog     = $this->initContactEventLog($contact, $eventName, $customItemId);
        $this->entityManager->persist($eventLog);
        $this->entityManager->flush();
    }

    /**
     * @param Lead    $contact
     * @param string  $action
     * @param int     $objectId
     * @param mixed[] $properties
     *
     * @return LeadEventLog
     */
    private function initContactEventLog(Lead $contact, string $action, int $objectId, array $properties = []): LeadEventLog
    {
        $eventLog = new LeadEventLog();
        $eventLog->setBundle('CustomObject');
        $eventLog->setObject('CustomItem');
        $eventLog->setLead($contact);
        $eventLog->setAction($action);
        $eventLog->setObjectId($objectId);
        $eventLog->setUserId($this->userHelper->getUser()->getId());
        $eventLog->setUserName($this->userHelper->getUser()->getName());
        $eventLog->setProperties($properties);

        return $eventLog;
    }
}
