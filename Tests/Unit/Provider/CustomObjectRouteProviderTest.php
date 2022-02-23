<?php

declare(strict_types=1);

namespace MauticPlugin\CustomObjectsBundle\Tests\Unit\Provider;

use MauticPlugin\CustomObjectsBundle\Provider\CustomObjectRouteProvider;
use Symfony\Component\Routing\RouterInterface;

class CustomObjectRouteProviderTest extends \PHPUnit\Framework\TestCase
{
    private const OBJECT_ID = 33;

    private $router;

    /**
     * @var CustomObjectRouteProvider
     */
    private $customObjectRouteProvider;

    protected function setUp(): void
    {
        parent::setUp();

        $this->router                    = $this->createMock(RouterInterface::class);
        $this->customObjectRouteProvider = new CustomObjectRouteProvider($this->router);
    }

    public function testBuildListRouteWithType(): void
    {
        $this->router->expects($this->once())
            ->method('generate')
            ->with(CustomObjectRouteProvider::ROUTE_LIST, ['page' => 3])
            ->willReturn('the/generated/route');

        $this->customObjectRouteProvider->buildListRoute(3);
    }

    public function testBuildSaveRoute(): void
    {
        $this->router->expects($this->once())
            ->method('generate')
            ->with(CustomObjectRouteProvider::ROUTE_SAVE, ['objectId' => null])
            ->willReturn('the/generated/route');

        $this->customObjectRouteProvider->buildSaveRoute();
    }

    public function testBuildSaveRouteWithId(): void
    {
        $this->router->expects($this->once())
            ->method('generate')
            ->with(CustomObjectRouteProvider::ROUTE_SAVE, ['objectId' => self::OBJECT_ID])
            ->willReturn('the/generated/route');

        $this->customObjectRouteProvider->buildSaveRoute(self::OBJECT_ID);
    }

    public function testBuildViewRoute(): void
    {
        $this->router->expects($this->once())
            ->method('generate')
            ->with(CustomObjectRouteProvider::ROUTE_VIEW, ['objectId' => self::OBJECT_ID])
            ->willReturn('the/generated/route');

        $this->customObjectRouteProvider->buildViewRoute(self::OBJECT_ID);
    }

    public function testBuildNewRoute(): void
    {
        $this->router->expects($this->once())
            ->method('generate')
            ->with(CustomObjectRouteProvider::ROUTE_NEW)
            ->willReturn('the/generated/route');

        $this->customObjectRouteProvider->buildNewRoute();
    }

    public function testBuildEditRoute(): void
    {
        $this->router->expects($this->once())
            ->method('generate')
            ->with(CustomObjectRouteProvider::ROUTE_EDIT, ['objectId' => null])
            ->willReturn('the/generated/route');

        $this->customObjectRouteProvider->buildEditRoute();
    }

    public function testBuildEditRouteWithId(): void
    {
        $this->router->expects($this->once())
            ->method('generate')
            ->with(CustomObjectRouteProvider::ROUTE_EDIT, ['objectId' => self::OBJECT_ID])
            ->willReturn('the/generated/route');

        $this->customObjectRouteProvider->buildEditRoute(self::OBJECT_ID);
    }

    public function testBuildCloneRoute(): void
    {
        $this->router->expects($this->once())
            ->method('generate')
            ->with(CustomObjectRouteProvider::ROUTE_CLONE, ['objectId' => 45])
            ->willReturn('the/generated/route');

        $this->customObjectRouteProvider->buildCloneRoute(45);
    }

    public function testBuildDeleteRoute(): void
    {
        $this->router->expects($this->once())
            ->method('generate')
            ->with(CustomObjectRouteProvider::ROUTE_DELETE, ['objectId' => 45])
            ->willReturn('the/generated/route');

        $this->customObjectRouteProvider->buildDeleteRoute(45);
    }
}
