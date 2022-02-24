<?php

declare(strict_types=1);

namespace MauticPlugin\CustomObjectsBundle\Tests\Unit\Provider;

use MauticPlugin\CustomObjectsBundle\Provider\CustomFieldRouteProvider;
use Symfony\Component\Routing\RouterInterface;

class CustomFieldRouteProviderTest extends \PHPUnit\Framework\TestCase
{
    private $router;

    /**
     * @var CustomFieldRouteProvider
     */
    private $customFieldRouteProvider;

    protected function setUp(): void
    {
        parent::setUp();

        $this->router                   = $this->createMock(RouterInterface::class);
        $this->customFieldRouteProvider = new CustomFieldRouteProvider($this->router);
    }

    public function testBuildSaveRouteWithType(): void
    {
        $this->router->expects($this->once())
            ->method('generate')
            ->with(CustomFieldRouteProvider::ROUTE_SAVE, ['fieldType' => 'text'])
            ->willReturn('the/generated/route');

        $this->customFieldRouteProvider->buildSaveRoute('text');
    }

    public function testBuildSaveRouteWithTypeAndId(): void
    {
        $this->router->expects($this->once())
            ->method('generate')
            ->with(CustomFieldRouteProvider::ROUTE_SAVE, ['fieldType' => 'text', 'fieldId' => 44])
            ->willReturn('the/generated/route');

        $this->customFieldRouteProvider->buildSaveRoute('text', 44);
    }

    public function testBuildSaveRouteWithTypeAndIdAndObjectId(): void
    {
        $this->router->expects($this->once())
            ->method('generate')
            ->with(CustomFieldRouteProvider::ROUTE_SAVE, ['fieldType' => 'text', 'fieldId' => 44, 'objectId' => 55])
            ->willReturn('the/generated/route');

        $this->customFieldRouteProvider->buildSaveRoute('text', 44, 55);
    }

    public function testBuildFormRoute(): void
    {
        $this->router->expects($this->once())
            ->method('generate')
            ->with(CustomFieldRouteProvider::ROUTE_FORM, [])
            ->willReturn('the/generated/route');

        $this->customFieldRouteProvider->buildFormRoute();
    }

    public function testBuildFormRouteWithId(): void
    {
        $this->router->expects($this->once())
            ->method('generate')
            ->with(CustomFieldRouteProvider::ROUTE_FORM, ['fieldId' => 45])
            ->willReturn('the/generated/route');

        $this->customFieldRouteProvider->buildFormRoute(45);
    }
}
