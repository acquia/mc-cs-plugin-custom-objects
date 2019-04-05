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

namespace MauticPlugin\CustomObjectsBundle\Tests\Unit\Controller\CustomObject;

use MauticPlugin\CustomObjectsBundle\Provider\CustomObjectRouteProvider;
use MauticPlugin\CustomObjectsBundle\Tests\Unit\Controller\ControllerTestCase;
use MauticPlugin\CustomObjectsBundle\Controller\CustomObject\CancelController;
use MauticPlugin\CustomObjectsBundle\Provider\CustomObjectSessionProvider;

class CancelControllerTest extends ControllerTestCase
{
    private $sessionProvider;
    private $routeProvider;

    /**
     * @var CancelController
     */
    private $cancelController;

    protected function setUp(): void
    {
        parent::setUp();

        $this->sessionProvider  = $this->createMock(CustomObjectSessionProvider::class);
        $this->routeProvider    = $this->createMock(CustomObjectRouteProvider::class);
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
            ->with(4)
            ->willReturn('some/route');

        $this->cancelController->cancelAction(null);
    }
}
