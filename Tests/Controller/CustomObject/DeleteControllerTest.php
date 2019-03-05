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

namespace MauticPlugin\CustomObjectsBundle\Tests\Controller\CustomObject;

use Symfony\Component\HttpFoundation\Request;
use MauticPlugin\CustomObjectsBundle\Model\CustomObjectModel;
use MauticPlugin\CustomObjectsBundle\Provider\CustomObjectPermissionProvider;
use MauticPlugin\CustomObjectsBundle\Controller\CustomObject\DeleteController;
use MauticPlugin\CustomObjectsBundle\Exception\NotFoundException;
use MauticPlugin\CustomObjectsBundle\Exception\ForbiddenException;
use MauticPlugin\CustomObjectsBundle\Entity\CustomObject;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Mautic\CoreBundle\Service\FlashBag;
use MauticPlugin\CustomObjectsBundle\Provider\CustomObjectSessionProvider;
use MauticPlugin\CustomObjectsBundle\Tests\Controller\ControllerTestCase;

class DeleteControllerTest extends ControllerTestCase
{
    private const OBJECT_ID = 33;

    private $customObjectModel;
    private $sessionprovider;
    private $flashBag;
    private $permissionProvider;

    /**
     * @var DeleteController
     */
    private $deleteController;

    protected function setUp(): void
    {
        parent::setUp();

        $this->customObjectModel  = $this->createMock(CustomObjectModel::class);
        $this->sessionprovider    = $this->createMock(CustomObjectSessionProvider::class);
        $this->flashBag           = $this->createMock(FlashBag::class);
        $this->permissionProvider = $this->createMock(CustomObjectPermissionProvider::class);
        $this->request            = $this->createMock(Request::class);
        $this->deleteController   = new DeleteController(
            $this->customObjectModel,
            $this->sessionprovider,
            $this->flashBag,
            $this->permissionProvider
        );

        $this->addSymfonyDependencies($this->deleteController);

        $this->request->method('isXmlHttpRequest')->willReturn(true);
        $this->request->method('getRequestUri')->willReturn('https://a.b');
    }

    public function testDeleteActionIfCustomObjectNotFound(): void
    {
        $this->customObjectModel->expects($this->once())
            ->method('fetchEntity')
            ->will($this->throwException(new NotFoundException('Object not found message')));

        $this->permissionProvider->expects($this->never())
            ->method('canDelete');

        $this->flashBag->expects($this->never())
            ->method('add');

        $this->deleteController->deleteAction(self::OBJECT_ID);
    }

    public function testDeleteActionIfCustomObjectForbidden(): void
    {
        $this->customObjectModel->expects($this->once())
            ->method('fetchEntity')
            ->willReturn($this->createMock(CustomObject::class));

        $this->permissionProvider->expects($this->once())
            ->method('canDelete')
            ->will($this->throwException(new ForbiddenException('delete')));

        $this->customObjectModel->expects($this->never())
            ->method('delete');

        $this->flashBag->expects($this->never())
            ->method('add');

        $this->expectException(AccessDeniedHttpException::class);

        $this->deleteController->deleteAction(self::OBJECT_ID);
    }

    public function testDeleteAction(): void
    {
        $customObject = $this->createMock(CustomObject::class);

        $customObject->method('getId')->willReturn(self::OBJECT_ID);

        $this->customObjectModel->expects($this->once())
            ->method('fetchEntity')
            ->with(self::OBJECT_ID)
            ->willReturn($customObject);

        $this->customObjectModel->expects($this->once())
            ->method('delete')
            ->with($customObject);

        $this->flashBag->expects($this->once())
            ->method('add')
            ->with('mautic.core.notice.deleted');

        $this->sessionprovider->expects($this->once())
            ->method('getPage')
            ->willReturn(3);

        $this->deleteController->deleteAction(self::OBJECT_ID);
    }
}
