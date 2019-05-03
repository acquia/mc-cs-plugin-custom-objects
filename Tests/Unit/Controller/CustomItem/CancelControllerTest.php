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

namespace MauticPlugin\CustomObjectsBundle\Tests\Unit\Controller\CustomItem;

use MauticPlugin\CustomObjectsBundle\Entity\CustomItem;
use MauticPlugin\CustomObjectsBundle\Entity\CustomObject;
use MauticPlugin\CustomObjectsBundle\Model\CustomItemModel;
use MauticPlugin\CustomObjectsBundle\Provider\CustomItemRouteProvider;
use MauticPlugin\CustomObjectsBundle\Tests\Unit\Controller\ControllerTestCase;
use MauticPlugin\CustomObjectsBundle\Controller\CustomItem\CancelController;
use MauticPlugin\CustomObjectsBundle\Provider\CustomItemSessionProvider;

class CancelControllerTest extends ControllerTestCase
{
    private const OBJECT_ID = 33;

    private $sessionProvider;
    private $routeProvider;
    private $customItemModel;

    /**
     * @var CancelController
     */
    private $cancelController;

    protected function setUp(): void
    {
        parent::setUp();

        $this->sessionProvider  = $this->createMock(CustomItemSessionProvider::class);
        $this->routeProvider    = $this->createMock(CustomItemRouteProvider::class);
        $this->customItemModel = $this->createMock(CustomItemModel::class);

        $this->cancelController = new CancelController(
            $this->sessionProvider,
            $this->routeProvider,
            $this->customItemModel
        );

        $this->addSymfonyDependencies($this->cancelController);
    }

    public function testCancelAction(): void
    {
        $pageNumber = 4;

        $this->sessionProvider->expects($this->once())
            ->method('getPage')
            ->willReturn($pageNumber);

        $this->customItemModel->expects($this->once())
            ->method('getEntity')
            ->with(null)
            ->willReturn(null);

        $this->routeProvider->expects($this->once())
            ->method('buildListRoute')
            ->with(self::OBJECT_ID, $pageNumber)
            ->willReturn('some/route');

        $this->cancelController->cancelAction(self::OBJECT_ID);
    }

    public function testCancelActionWithEntityUnlock(): void
    {
        $pageNumber     = 2;
        $customItemId   = 4;
        $customItem     = new CustomItem(new CustomObject());

        $this->sessionProvider->expects($this->once())
            ->method('getPage')
            ->willReturn($pageNumber);

        $this->customItemModel->expects($this->once())
            ->method('getEntity')
            ->with($customItemId)
            ->willReturn($customItem);

        $this->customItemModel->expects($this->once())
            ->method('unlockEntity')
            ->with($customItem);

        $this->routeProvider->expects($this->once())
            ->method('buildListRoute')
            ->with(self::OBJECT_ID, $pageNumber)
            ->willReturn('some/route');

        $this->cancelController->cancelAction(self::OBJECT_ID, $customItemId);
    }
}
