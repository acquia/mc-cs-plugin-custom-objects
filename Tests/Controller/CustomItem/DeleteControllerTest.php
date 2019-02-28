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

namespace MauticPlugin\CustomObjectsBundle\Tests\Controller\CustomItem;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Translation\TranslatorInterface;
use MauticPlugin\CustomObjectsBundle\Model\CustomItemModel;
use MauticPlugin\CustomObjectsBundle\Provider\CustomItemPermissionProvider;
use MauticPlugin\CustomObjectsBundle\Controller\CustomItem\DeleteController;
use MauticPlugin\CustomObjectsBundle\Provider\CustomItemRouteProvider;
use MauticPlugin\CustomObjectsBundle\Provider\CustomItemSessionProvider;
use MauticPlugin\CustomObjectsBundle\Exception\NotFoundException;
use MauticPlugin\CustomObjectsBundle\Tests\Controller\ControllerDependenciesTrait;
use MauticPlugin\CustomObjectsBundle\Exception\ForbiddenException;
use MauticPlugin\CustomObjectsBundle\Entity\CustomItem;
use Symfony\Component\HttpFoundation\HeaderBag;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class DeleteControllerTest extends \PHPUnit_Framework_TestCase
{
    use ControllerDependenciesTrait;

    private const OBJECT_ID = 33;

    private const ITEM_ID = 22;
    
    private $customItemModel;
    private $sessionprovider;
    private $translator;
    private $permissionProvider;
    private $routeProvider;
    private $request;

    /**
     * @var DeleteController
     */
    private $deleteController;

    protected function setUp()
    {
        parent::setUp();

        $this->customItemModel    = $this->createMock(CustomItemModel::class);
        $this->sessionprovider    = $this->createMock(CustomItemSessionProvider::class);
        $this->translator         = $this->createMock(TranslatorInterface::class);
        $this->permissionProvider = $this->createMock(CustomItemPermissionProvider::class);
        $this->routeProvider      = $this->createMock(CustomItemRouteProvider::class);
        $this->request            = $this->createMock(Request::class);
        $this->deleteController   = new DeleteController(
            $this->customItemModel,
            $this->sessionprovider,
            $this->translator,
            $this->permissionProvider,
            $this->routeProvider 
        );

        $this->addSymfonyDependencies($this->deleteController);
        
        $this->request->method('isXmlHttpRequest')->willReturn(true);
        $this->request->method('getRequestUri')->willReturn('https://a.b');
        $this->request->headers = new HeaderBag();
    }

    public function testDeleteActionIfCustomItemNotFound()
    {
        $this->customItemModel->expects($this->once())
            ->method('fetchEntity')
            ->will($this->throwException(new NotFoundException('Item not found message')));

        $this->customItemModel->expects($this->never())
            ->method('delete');

        $this->translator->expects($this->once())
            ->method('trans')
            ->with('Item not found message', ['%url%' => 'https://a.b'])
            ->willReturn('some translation');
        
        $this->sessionprovider->expects($this->never())
            ->method('addFlash');

        $this->deleteController->deleteAction(self::OBJECT_ID, self::ITEM_ID);
    }

    public function testDeleteActionIfCustomItemForbidden()
    {
        $this->customItemModel->expects($this->once())
            ->method('fetchEntity')
            ->willReturn($this->createMock(CustomItem::class));

        $this->permissionProvider->expects($this->once())
            ->method('canDelete')
            ->will($this->throwException(new ForbiddenException('delete')));

        $this->customItemModel->expects($this->never())
            ->method('delete');

        $this->translator->expects($this->once())
            ->method('trans')
            ->with('You do not have permission to delete', ['%url%' => 'https://a.b'])
            ->willReturn('some translation');
        
        $this->sessionprovider->expects($this->never())
            ->method('addFlash');

        $this->expectException(AccessDeniedHttpException::class);

        $this->deleteController->deleteAction(self::OBJECT_ID, self::ITEM_ID);
    }

    public function testDeleteAction()
    {
        $customItem = $this->createMock(CustomItem::class);

        $customItem->method('getId')->willReturn(self::ITEM_ID);
        $customItem->method('getName')->willReturn('Apple');

        $this->customItemModel->expects($this->once())
            ->method('fetchEntity')
            ->with(self::ITEM_ID)
            ->willReturn($customItem);

        $this->customItemModel->expects($this->once())
            ->method('delete')
            ->with($customItem);
            
        $this->translator->expects($this->once())
            ->method('trans')
            ->with('mautic.core.notice.deleted', ['%name%' => 'Apple', '%id%' => self::ITEM_ID], 'flashes')
            ->willReturn('some translation');
        
        $this->sessionprovider->expects($this->once())
            ->method('addFlash')
            ->with('some translation', 'notice');

        $this->sessionprovider->expects($this->once())
            ->method('getPage')
            ->willReturn(3);

        $this->routeProvider->expects($this->once())
            ->method('buildListRoute')
            ->with(self::OBJECT_ID, 3);

        $this->deleteController->deleteAction(self::OBJECT_ID, self::ITEM_ID);
    }
}

