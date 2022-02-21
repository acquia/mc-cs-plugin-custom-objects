<?php

declare(strict_types=1);

namespace MauticPlugin\CustomObjectsBundle\Tests\Unit\Provider;

use MauticPlugin\CustomObjectsBundle\Provider\CustomItemRouteProvider;
use Symfony\Component\Routing\RouterInterface;

class CustomItemRouteProviderTest extends \PHPUnit\Framework\TestCase
{
    private const ITEM_ID   = 44;
    private const OBJECT_ID = 33;

    private $router;

    /**
     * @var CustomItemRouteProvider
     */
    private $customItemRouteProvider;

    protected function setUp(): void
    {
        parent::setUp();

        $this->router                   = $this->createMock(RouterInterface::class);
        $this->customItemRouteProvider  = new CustomItemRouteProvider($this->router);
    }

    public function testBuildListRoute(): void
    {
        $this->router->expects($this->once())
            ->method('generate')
            ->with(CustomItemRouteProvider::ROUTE_LIST, [
                'objectId'         => self::OBJECT_ID,
                'page'             => 3,
                'filterEntityType' => null,
                'filterEntityId'   => null,
            ])
            ->willReturn('the/generated/route');

        $this->customItemRouteProvider->buildListRoute(self::OBJECT_ID, 3);
    }

    public function testUnlinkContactRoute(): void
    {
        $this->router->expects($this->once())
            ->method('generate')
            ->with(CustomItemRouteProvider::ROUTE_UNLINK, ['itemId' => self::ITEM_ID, 'entityType' => 'contact', 'entityId' => 66])
            ->willReturn('the/generated/route');

        $this->customItemRouteProvider->buildUnlinkRoute(self::ITEM_ID, 'contact', 66);
    }

    public function testBuildNewRoute(): void
    {
        $this->router->expects($this->once())
            ->method('generate')
            ->with(CustomItemRouteProvider::ROUTE_NEW, ['objectId' => self::OBJECT_ID])
            ->willReturn('the/generated/route');

        $this->customItemRouteProvider->buildNewRoute(self::OBJECT_ID);
    }

    public function testBuildSaveRoute(): void
    {
        $this->router->expects($this->once())
            ->method('generate')
            ->with(CustomItemRouteProvider::ROUTE_SAVE, ['objectId' => self::OBJECT_ID, 'itemId' => null])
            ->willReturn('the/generated/route');

        $this->customItemRouteProvider->buildSaveRoute(self::OBJECT_ID);
    }

    public function testBuildSaveRouteWithId(): void
    {
        $this->router->expects($this->once())
            ->method('generate')
            ->with(CustomItemRouteProvider::ROUTE_SAVE, ['objectId' => self::OBJECT_ID, 'itemId' => self::ITEM_ID])
            ->willReturn('the/generated/route');

        $this->customItemRouteProvider->buildSaveRoute(self::OBJECT_ID, self::ITEM_ID);
    }

    public function testBuildViewRoute(): void
    {
        $this->router->expects($this->once())
            ->method('generate')
            ->with(CustomItemRouteProvider::ROUTE_VIEW, ['objectId' => self::OBJECT_ID, 'itemId' => self::ITEM_ID])
            ->willReturn('the/generated/route');

        $this->customItemRouteProvider->buildViewRoute(self::OBJECT_ID, self::ITEM_ID);
    }

    public function testBuildEditRoute(): void
    {
        $this->router->expects($this->once())
            ->method('generate')
            ->with(CustomItemRouteProvider::ROUTE_EDIT, ['objectId' => self::OBJECT_ID, 'itemId' => self::ITEM_ID])
            ->willReturn('the/generated/route');

        $this->customItemRouteProvider->buildEditRoute(self::OBJECT_ID, self::ITEM_ID);
    }

    public function testBuildCloneRoute(): void
    {
        $this->router->expects($this->once())
            ->method('generate')
            ->with(CustomItemRouteProvider::ROUTE_CLONE, ['objectId' => self::OBJECT_ID, 'itemId' => self::ITEM_ID])
            ->willReturn('the/generated/route');

        $this->customItemRouteProvider->buildCloneRoute(self::OBJECT_ID, self::ITEM_ID);
    }

    public function testBuildDeleteRoute(): void
    {
        $this->router->expects($this->once())
            ->method('generate')
            ->with(CustomItemRouteProvider::ROUTE_DELETE, ['objectId' => self::OBJECT_ID, 'itemId' => self::ITEM_ID])
            ->willReturn('the/generated/route');

        $this->customItemRouteProvider->buildDeleteRoute(self::OBJECT_ID, self::ITEM_ID);
    }

    public function testBuildBatchDeleteRoute(): void
    {
        $this->router->expects($this->once())
            ->method('generate')
            ->with(CustomItemRouteProvider::ROUTE_BATCH_DELETE, ['objectId' => self::OBJECT_ID])
            ->willReturn('the/generated/route');

        $this->customItemRouteProvider->buildBatchDeleteRoute(self::OBJECT_ID);
    }

    public function testBuildLookupRoute(): void
    {
        $this->router->expects($this->once())
            ->method('generate')
            ->with(
                CustomItemRouteProvider::ROUTE_LOOKUP,
                [
                    'objectId'         => self::OBJECT_ID,
                    'filterEntityType' => null,
                    'filterEntityId'   => null,
                ]
            )
            ->willReturn('the/generated/route');

        $this->customItemRouteProvider->buildLookupRoute(self::OBJECT_ID);
    }

    public function testBuildNewImportRoute(): void
    {
        $this->router->expects($this->once())
            ->method('generate')
            ->with(CustomItemRouteProvider::ROUTE_IMPORT_ACTION, ['object' => 'custom-object:33', 'objectAction' => 'new'])
            ->willReturn('the/generated/route');

        $this->customItemRouteProvider->buildNewImportRoute(self::OBJECT_ID);
    }

    public function testBuildListImportRoute(): void
    {
        $this->router->expects($this->once())
            ->method('generate')
            ->with(CustomItemRouteProvider::ROUTE_IMPORT_LIST, ['object' => 'custom-object:33'])
            ->willReturn('the/generated/route');

        $this->customItemRouteProvider->buildListImportRoute(self::OBJECT_ID);
    }

    public function testThatBuildNewRouteWithRedirectToContactReturnsCorrectUrl()
    {
        $this->router->expects($this->once())
            ->method('generate')
            ->with(CustomItemRouteProvider::ROUTE_NEW_REDIRECT_TO_CONTACT, ['objectId' => 1, 'contactId' => 1])
            ->willReturn('the/generated/route');

        $this->customItemRouteProvider->buildNewRouteWithRedirectToContact(1, 1);
    }

    public function testThatBuildEditRouteWithRedirectToContactReturnsCorrectUrl()
    {
        $this->router->expects($this->once())
            ->method('generate')
            ->with(CustomItemRouteProvider::ROUTE_EDIT_REDIRECT_TO_CONTACT, ['objectId' => 1, 'itemId' => 1, 'contactId' => 1])
            ->willReturn('the/generated/route');

        $this->customItemRouteProvider->buildEditRouteWithRedirectToContact(1, 1, 1);
    }

    public function testThatBuildContactViewRouteReturnsCorrectUrl()
    {
        $this->router->expects($this->once())
            ->method('generate')
            ->with('mautic_contact_action', ['objectAction' => 'view', 'objectId' => 1])
            ->willReturn('the/generated/route');

        $this->customItemRouteProvider->buildContactViewRoute(1);
    }
}
