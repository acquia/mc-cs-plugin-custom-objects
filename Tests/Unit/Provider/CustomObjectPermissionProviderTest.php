<?php

declare(strict_types=1);

namespace MauticPlugin\CustomObjectsBundle\Tests\Unit\Provider;

use Mautic\CoreBundle\Entity\FormEntity;
use Mautic\CoreBundle\Security\Permissions\CorePermissions;
use MauticPlugin\CustomObjectsBundle\Exception\ForbiddenException;
use MauticPlugin\CustomObjectsBundle\Provider\CustomObjectPermissionProvider;

/**
 * Tests methods in the AbstractPermissionProvider by CustomObjectPermissionProvider.
 */
class CustomObjectPermissionProviderTest extends \PHPUnit\Framework\TestCase
{
    private $permissions;
    private $entity;

    /**
     * @var CustomObjectPermissionProvider
     */
    private $provider;

    protected function setUp(): void
    {
        parent::setUp();
        $this->permissions = $this->createMock(CorePermissions::class);
        $this->entity      = $this->createMock(FormEntity::class);
        $this->provider    = new CustomObjectPermissionProvider($this->permissions);
    }

    public function testIsGrantedTrue(): void
    {
        $this->permissions->expects($this->once())
            ->method('isGranted')
            ->with('custom_objects:custom_objects:create')
            ->willReturn(true);

        $this->provider->isGranted('create');
    }

    public function testIsGrantedFalse(): void
    {
        $this->permissions->expects($this->once())
            ->method('isGranted')
            ->with('custom_objects:custom_objects:create')
            ->willReturn(false);

        $this->expectException(ForbiddenException::class);

        $this->provider->isGranted('create');
    }

    public function testHasEntityAccessTrue(): void
    {
        $this->permissions->expects($this->once())
            ->method('hasEntityAccess')
            ->with('custom_objects:custom_objects:deleteown')
            ->willReturn(true);

        $this->provider->hasEntityAccess('delete', $this->entity);
    }

    public function testHasEntityAccessFalse(): void
    {
        $this->permissions->expects($this->once())
            ->method('hasEntityAccess')
            ->with('custom_objects:custom_objects:deleteown')
            ->willReturn(false);

        $this->expectException(ForbiddenException::class);

        $this->provider->hasEntityAccess('delete', $this->entity);
    }

    public function testCanCreateTrue(): void
    {
        $this->permissions->expects($this->once())
            ->method('isGranted')
            ->with('custom_objects:custom_objects:create')
            ->willReturn(true);

        $this->provider->canCreate();
    }

    public function testCanCreateFalse(): void
    {
        $this->permissions->expects($this->once())
            ->method('isGranted')
            ->with('custom_objects:custom_objects:create')
            ->willReturn(false);

        $this->expectException(ForbiddenException::class);

        $this->provider->canCreate();
    }

    public function testCanViewTrue(): void
    {
        $this->permissions->expects($this->once())
            ->method('hasEntityAccess')
            ->with('custom_objects:custom_objects:viewown')
            ->willReturn(true);

        $this->provider->canView($this->entity);
    }

    public function testCanViewFalse(): void
    {
        $this->permissions->expects($this->once())
            ->method('hasEntityAccess')
            ->with('custom_objects:custom_objects:viewown')
            ->willReturn(false);

        $this->expectException(ForbiddenException::class);

        $this->provider->canView($this->entity);
    }

    public function testCanViewAtAllTrue(): void
    {
        $this->permissions->expects($this->once())
            ->method('isGranted')
            ->with('custom_objects:custom_objects:view')
            ->willReturn(true);

        $this->provider->canViewAtAll();
    }

    public function testCanViewAtAllFalse(): void
    {
        $this->permissions->expects($this->once())
            ->method('isGranted')
            ->with('custom_objects:custom_objects:view')
            ->willReturn(false);

        $this->expectException(ForbiddenException::class);

        $this->provider->canViewAtAll();
    }

    public function testCanEditTrue(): void
    {
        $this->permissions->expects($this->once())
            ->method('hasEntityAccess')
            ->with('custom_objects:custom_objects:editown')
            ->willReturn(true);

        $this->provider->canEdit($this->entity);
    }

    public function testCanEditFalse(): void
    {
        $this->permissions->expects($this->once())
            ->method('hasEntityAccess')
            ->with('custom_objects:custom_objects:editown')
            ->willReturn(false);

        $this->expectException(ForbiddenException::class);

        $this->provider->canEdit($this->entity);
    }

    public function testCanCloneTrue(): void
    {
        $this->permissions->expects($this->once())
            ->method('isGranted')
            ->with('custom_objects:custom_objects:create')
            ->willReturn(true);

        $this->permissions->expects($this->once())
            ->method('hasEntityAccess')
            ->with('custom_objects:custom_objects:viewown')
            ->willReturn(true);

        $this->provider->canClone($this->entity);
    }

    public function testCanCloneFalse(): void
    {
        $this->permissions->expects($this->once())
            ->method('isGranted')
            ->with('custom_objects:custom_objects:create')
            ->willReturn(true);

        $this->permissions->expects($this->once())
            ->method('hasEntityAccess')
            ->with('custom_objects:custom_objects:viewown')
            ->willReturn(false);

        $this->expectException(ForbiddenException::class);

        $this->provider->canClone($this->entity);
    }

    public function testCanDeleteTrue(): void
    {
        $this->permissions->expects($this->once())
            ->method('hasEntityAccess')
            ->with('custom_objects:custom_objects:deleteown')
            ->willReturn(true);

        $this->provider->canDelete($this->entity);
    }

    public function testCanDeleteFalse(): void
    {
        $this->permissions->expects($this->once())
            ->method('hasEntityAccess')
            ->with('custom_objects:custom_objects:deleteown')
            ->willReturn(false);

        $this->expectException(ForbiddenException::class);

        $this->provider->canDelete($this->entity);
    }
}
