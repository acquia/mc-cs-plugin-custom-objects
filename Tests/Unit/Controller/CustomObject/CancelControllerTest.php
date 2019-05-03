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

use MauticPlugin\CustomObjectsBundle\Entity\CustomObject;
use MauticPlugin\CustomObjectsBundle\Model\CustomObjectModel;
use MauticPlugin\CustomObjectsBundle\Provider\CustomObjectRouteProvider;
use MauticPlugin\CustomObjectsBundle\Tests\Unit\Controller\ControllerTestCase;
use MauticPlugin\CustomObjectsBundle\Controller\CustomObject\CancelController;
use MauticPlugin\CustomObjectsBundle\Provider\CustomObjectSessionProvider;

class CancelControllerTest extends ControllerTestCase
{
    private $sessionProvider;
    private $routeProvider;
    private $customObjectModel;

    /**
     * @var CancelController
     */
    private $cancelController;

    protected function setUp(): void
    {
        parent::setUp();

        $this->sessionProvider   = $this->createMock(CustomObjectSessionProvider::class);
        $this->routeProvider     = $this->createMock(CustomObjectRouteProvider::class);
        $this->customObjectModel = $this->createMock(CustomObjectModel::class);

        $this->cancelController = new CancelController(
            $this->sessionProvider,
            $this->routeProvider,
            $this->customObjectModel
        );

        $this->addSymfonyDependencies($this->cancelController);
    }

    public function testCancelAction(): void
    {
        $pageNumber = 4;

        $this->sessionProvider->expects($this->once())
            ->method('getPage')
            ->willReturn($pageNumber);

        $this->routeProvider->expects($this->once())
            ->method('buildListRoute')
            ->with($pageNumber)
            ->willReturn('some/route');

        $this->customObjectModel->expects($this->once())
            ->method('getEntity')
            ->with(null)
            ->willReturn(null);

        $this->cancelController->cancelAction(null);
    }

    public function testCancelActionWithEntityUnlock(): void
    {
        $pageNumber     = 2;
        $customObjectId = 3;
        $customObject   = new CustomObject();

        $this->sessionProvider->expects($this->once())
            ->method('getPage')
            ->willReturn($pageNumber);

        $this->routeProvider->expects($this->once())
            ->method('buildListRoute')
            ->with($pageNumber)
            ->willReturn('some/route');

        $this->customObjectModel->expects($this->once())
            ->method('getEntity')
            ->with($customObjectId)
            ->willReturn($customObject);

        $this->customObjectModel->expects($this->once())
            ->method('unlockEntity')
            ->with($customObject);

        $this->cancelController->cancelAction($customObjectId);
    }
}
