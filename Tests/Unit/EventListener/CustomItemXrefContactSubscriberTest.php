<?php

declare(strict_types=1);

/*
 * @copyright   2019 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\CustomObjectsBundle\Tests\Unit\EventListener;

use MauticPlugin\CustomObjectsBundle\EventListener\CustomItemXrefContactSubscriber;
use MauticPlugin\CustomObjectsBundle\Event\CustomItemXrefContactEvent;
use Doctrine\ORM\EntityManager;
use Mautic\LeadBundle\Entity\Lead;
use MauticPlugin\CustomObjectsBundle\Entity\CustomItem;
use MauticPlugin\CustomObjectsBundle\Entity\CustomItemXrefContact;
use Mautic\CoreBundle\Helper\UserHelper;
use Mautic\UserBundle\Entity\User;
use Mautic\LeadBundle\Entity\LeadEventLog;

class CustomItemXrefContactSubscriberTest extends \PHPUnit_Framework_TestCase
{
    private const ITEM_ID = 90;

    private const USER_ID = 4;

    private const USER_NAME = 'Joe';

    private $userHelper;

    private $user;

    private $entityManager;

    private $event;

    private $contact;

    private $customItem;

    private $xref;

    private $xrefSubscriber;

    protected function setUp(): void
    {
        parent::setUp();

        $this->entityManager  = $this->createMock(EntityManager::class);
        $this->userHelper     = $this->createMock(UserHelper::class);
        $this->user           = $this->createMock(User::class);
        $this->event          = $this->createMock(CustomItemXrefContactEvent::class);
        $this->contact        = $this->createMock(Lead::class);
        $this->customItem     = $this->createMock(CustomItem::class);
        $this->xref           = $this->createMock(CustomItemXrefContact::class);
        $this->xrefSubscriber = new CustomItemXrefContactSubscriber(
            $this->entityManager,
            $this->userHelper
        );

        $this->event->method('getXref')->willReturn($this->xref);
        $this->xref->method('getContact')->willReturn($this->contact);
        $this->xref->method('getCustomItem')->willReturn($this->customItem);
        $this->customItem->method('getId')->willReturn(self::ITEM_ID);
        $this->userHelper->method('getUser')->willReturn($this->user);
        $this->user->method('getId')->willReturn(self::USER_ID);
        $this->user->method('getName')->willReturn(self::USER_NAME);
    }

    public function testOnLinkedContact(): void
    {
        $this->entityManager->expects($this->once())
            ->method('persist')
            ->with($this->callback($this->makePersistCallback('link')));

        $this->entityManager->expects($this->once())
            ->method('flush');

        $this->xrefSubscriber->onLinkedContact($this->event);
    }

    public function testOnUnlinkedContact(): void
    {
        $this->entityManager->expects($this->once())
            ->method('persist')
            ->with($this->callback($this->makePersistCallback('unlink')));

        $this->entityManager->expects($this->once())
            ->method('flush');

        $this->xrefSubscriber->onUnlinkedContact($this->event);
    }

    /**
     * @param string $action
     *
     * @return callable
     */
    private function makePersistCallback(string $action): callable
    {
        return function (LeadEventLog $eventLog) use ($action) {
            $this->assertSame(self::USER_ID, $eventLog->getUserId());
            $this->assertSame(self::USER_NAME, $eventLog->getUserName());
            $this->assertSame('CustomObject', $eventLog->getBundle());
            $this->assertSame('CustomItem', $eventLog->getObject());
            $this->assertSame(self::ITEM_ID, $eventLog->getObjectId());
            $this->assertSame($action, $eventLog->getAction());
            $this->assertSame($this->contact, $eventLog->getLead());

            return true;
        };
    }
}
