<?php

declare(strict_types=1);

namespace MauticPlugin\CustomObjectsBundle\Tests\Unit\Provider;

use Mautic\CoreBundle\Security\Permissions\CorePermissions;
use MauticPlugin\CustomObjectsBundle\Entity\CustomItem;
use MauticPlugin\CustomObjectsBundle\Entity\CustomObject;
use MauticPlugin\CustomObjectsBundle\Exception\ForbiddenException;
use MauticPlugin\CustomObjectsBundle\Provider\CustomItemPermissionProvider;

class CustomItemPermissionProviderTest extends \PHPUnit\Framework\TestCase
{
    private const OBJECT_ID = 33;

    private $corePermissions;
    private $customItem;
    private $customObject;

    /**
     * @var CustomItemPermissionProvider
     */
    private $provider;

    protected function setUp(): void
    {
        parent::setUp();

        $this->corePermissions = $this->createMock(CorePermissions::class);
        $this->customItem      = $this->createMock(CustomItem::class);
        $this->customObject    = $this->createMock(CustomObject::class);
        $this->provider        = new CustomItemPermissionProvider($this->corePermissions);

        $this->customObject->method('getId')->willReturn(self::OBJECT_ID);
        $this->customItem->method('getCustomObject')->willReturn($this->customObject);
    }

    public function testIsGrantedIfForbidden(): void
    {
        $this->corePermissions->expects($this->once())
            ->method('isGranted')
            ->with('custom_objects:33:unicorn')
            ->willReturn(false);

        $this->expectException(ForbiddenException::class);
        $this->provider->isGranted('unicorn', self::OBJECT_ID);
    }

    public function testHasEntityAccessIfForbidden(): void
    {
        $this->corePermissions->expects($this->once())
            ->method('hasEntityAccess')
            ->with('custom_objects:33:unicornown')
            ->willReturn(false);

        $this->expectException(ForbiddenException::class);
        $this->provider->hasEntityAccess('unicorn', $this->customItem);
    }

    public function testCanCreate(): void
    {
        $this->corePermissions->expects($this->once())
            ->method('isGranted')
            ->with('custom_objects:33:create')
            ->willReturn(true);

        $this->provider->canCreate(self::OBJECT_ID);
    }

    public function testCanView(): void
    {
        $this->corePermissions->expects($this->once())
            ->method('hasEntityAccess')
            ->with('custom_objects:33:viewown')
            ->willReturn(true);

        $this->provider->canView($this->customItem);
    }

    public function testCanViewAtAll(): void
    {
        $this->corePermissions->expects($this->once())
            ->method('isGranted')
            ->with('custom_objects:33:view')
            ->willReturn(true);

        $this->provider->canViewAtAll(self::OBJECT_ID);
    }

    public function testCanEdit(): void
    {
        $this->corePermissions->expects($this->once())
            ->method('hasEntityAccess')
            ->with('custom_objects:33:editown')
            ->willReturn(true);

        $this->provider->canEdit($this->customItem);
    }

    public function testCanClone(): void
    {
        $this->corePermissions->expects($this->once())
            ->method('hasEntityAccess')
            ->with('custom_objects:33:viewown')
            ->willReturn(true);

        $this->corePermissions->expects($this->once())
            ->method('isGranted')
            ->with('custom_objects:33:create')
            ->willReturn(true);

        $this->provider->canClone($this->customItem);
    }

    public function testCanDelete(): void
    {
        $this->corePermissions->expects($this->once())
            ->method('hasEntityAccess')
            ->with('custom_objects:33:deleteown')
            ->willReturn(true);

        $this->provider->canDelete($this->customItem);
    }
}
