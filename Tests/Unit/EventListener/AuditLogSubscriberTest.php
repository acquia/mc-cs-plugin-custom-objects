<?php

declare(strict_types=1);

/*
 * @copyright   2019 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc
 *
 * @link        https://mautic.com
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\CustomObjectsBundle\Tests\Unit\EventListener;

use MauticPlugin\CustomObjectsBundle\EventListener\AuditLogSubscriber;
use Mautic\CoreBundle\Model\AuditLogModel;
use Mautic\CoreBundle\Helper\IpLookupHelper;
use MauticPlugin\CustomObjectsBundle\Event\CustomItemEvent;
use MauticPlugin\CustomObjectsBundle\Entity\CustomItem;
use MauticPlugin\CustomObjectsBundle\Event\CustomObjectEvent;
use MauticPlugin\CustomObjectsBundle\Entity\CustomObject;

class AuditLogSubscriberTest extends \PHPUnit_Framework_TestCase
{
    private $auditLogModel;

    private $ipLookupHelper;

    private $customItemEvent;

    private $customObjectEvent;

    private $customItem;

    private $customObject;

    private $auditLogSubscriber;

    protected function setUp(): void
    {
        parent::setUp();

        $this->auditLogModel      = $this->createMock(AuditLogModel::class);
        $this->ipLookupHelper     = $this->createMock(IpLookupHelper::class);
        $this->customItemEvent    = $this->createMock(CustomItemEvent::class);
        $this->customObjectEvent  = $this->createMock(CustomObjectEvent::class);
        $this->customItem         = $this->createMock(CustomItem::class);
        $this->customObject       = $this->createMock(CustomObject::class);
        $this->auditLogSubscriber = new AuditLogSubscriber($this->auditLogModel, $this->ipLookupHelper);
    }

    public function testOnCustomItemPostSaveIfNoChanges(): void
    {
        $this->customItemEvent->expects($this->once())
            ->method('getCustomItem')
            ->willReturn($this->customItem);

        $this->customItem->expects($this->once())
            ->method('getChanges')
            ->willReturn([]);

        $this->auditLogModel->expects($this->never())
            ->method('writeToLog');

        $this->auditLogSubscriber->onCustomItemPostSave($this->customItemEvent);
    }

    public function testOnCustomItemPostSave(): void
    {
        $this->customItemEvent->expects($this->once())
            ->method('getCustomItem')
            ->willReturn($this->customItem);

        $this->customItem->expects($this->once())
            ->method('getChanges')
            ->willReturn(['some' => 'changes']);

        $this->customItem->expects($this->once())
            ->method('getId')
            ->willReturn(33);

        $this->customItemEvent->expects($this->once())
            ->method('entityIsNew')
            ->willReturn(true);

        $this->ipLookupHelper->expects($this->once())
            ->method('getIpAddressFromRequest')
            ->willReturn('127.0.0.1');

        $this->auditLogModel->expects($this->once())
            ->method('writeToLog')
            ->with([
                'bundle'    => 'customObjects',
                'object'    => 'customItem',
                'objectId'  => 33,
                'action'    => 'create',
                'details'   => ['some' => 'changes'],
                'ipAddress' => '127.0.0.1',
            ]);

        $this->auditLogSubscriber->onCustomItemPostSave($this->customItemEvent);
    }

    public function testOnCustomItemPostDelete(): void
    {
        $this->customItemEvent->expects($this->once())
            ->method('getCustomItem')
            ->willReturn($this->customItem);

        $this->customItem->expects($this->once())
            ->method('getName')
            ->willReturn('Item A');

        $this->customItem->deletedId = 33;

        $this->ipLookupHelper->expects($this->once())
            ->method('getIpAddressFromRequest')
            ->willReturn('127.0.0.1');

        $this->auditLogModel->expects($this->once())
            ->method('writeToLog')
            ->with([
                'bundle'    => 'customObjects',
                'object'    => 'customItem',
                'objectId'  => 33,
                'action'    => 'delete',
                'details'   => ['name' => 'Item A'],
                'ipAddress' => '127.0.0.1',
            ]);

        $this->auditLogSubscriber->onCustomItemPostDelete($this->customItemEvent);
    }

    public function testOnCustomObjectPostSaveIfNoChanges(): void
    {
        $this->customObjectEvent->expects($this->once())
            ->method('getCustomObject')
            ->willReturn($this->customObject);

        $this->customObject->expects($this->once())
            ->method('getChanges')
            ->willReturn([]);

        $this->auditLogModel->expects($this->never())
            ->method('writeToLog');

        $this->auditLogSubscriber->onCustomObjectPostSave($this->customObjectEvent);
    }

    public function testOnCustomObjectPostSave(): void
    {
        $this->customObjectEvent->expects($this->once())
            ->method('getCustomObject')
            ->willReturn($this->customObject);

        $this->customObject->expects($this->once())
            ->method('getChanges')
            ->willReturn(['some' => 'changes']);

        $this->customObject->expects($this->once())
            ->method('getId')
            ->willReturn(12);

        $this->customObjectEvent->expects($this->once())
            ->method('entityIsNew')
            ->willReturn(true);

        $this->ipLookupHelper->expects($this->once())
            ->method('getIpAddressFromRequest')
            ->willReturn('127.0.0.1');

        $this->auditLogModel->expects($this->once())
            ->method('writeToLog')
            ->with([
                'bundle'    => 'customObjects',
                'object'    => 'customObject',
                'objectId'  => 12,
                'action'    => 'create',
                'details'   => ['some' => 'changes'],
                'ipAddress' => '127.0.0.1',
            ]);

        $this->auditLogSubscriber->onCustomObjectPostSave($this->customObjectEvent);
    }

    public function testOnCustomObjectPostDelete(): void
    {
        $this->customObjectEvent->expects($this->once())
            ->method('getCustomObject')
            ->willReturn($this->customObject);

        $this->customObject->expects($this->once())
            ->method('getName')
            ->willReturn('Object A');

        $this->customObject->deletedId = 12;

        $this->ipLookupHelper->expects($this->once())
            ->method('getIpAddressFromRequest')
            ->willReturn('127.0.0.1');

        $this->auditLogModel->expects($this->once())
            ->method('writeToLog')
            ->with([
                'bundle'    => 'customObjects',
                'object'    => 'customObject',
                'objectId'  => 12,
                'action'    => 'delete',
                'details'   => ['name' => 'Object A'],
                'ipAddress' => '127.0.0.1',
            ]);

        $this->auditLogSubscriber->onCustomObjectPostDelete($this->customObjectEvent);
    }
}
