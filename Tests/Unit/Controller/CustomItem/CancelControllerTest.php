<?php

declare(strict_types=1);

/*
 * @copyright   2019 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\CustomObjectsBundle\Tests\Unit\Controller\CustomItem;

use MauticPlugin\CustomObjectsBundle\Provider\CustomItemRouteProvider;
use MauticPlugin\CustomObjectsBundle\Tests\Unit\Controller\ControllerTestCase;
use MauticPlugin\CustomObjectsBundle\Controller\CustomItem\CancelController;
use MauticPlugin\CustomObjectsBundle\Provider\CustomItemSessionProvider;

class CancelControllerTest extends ControllerTestCase
{
    private const OBJECT_ID = 33;

    private $sessionProvider;
    private $routeProvider;

    /**
     * @var CancelController
     */
    private $cancelController;

    protected function setUp(): void
    {
        parent::setUp();

        $this->sessionProvider  = $this->createMock(CustomItemSessionProvider::class);
        $this->routeProvider    = $this->createMock(CustomItemRouteProvider::class);
        $this->cancelController = new CancelController(
            $this->sessionProvider,
            $this->routeProvider
        );

        $this->addSymfonyDependencies($this->cancelController);
    }

    public function testCancelAction(): void
    {
        $this->sessionProvider->expects($this->once())
            ->method('getPage')
            ->willReturn(4);

        $this->routeProvider->expects($this->once())
            ->method('buildListRoute')
            ->with(self::OBJECT_ID, 4)
            ->willReturn('some/route');

        $this->cancelController->cancelAction(self::OBJECT_ID);
    }
}
