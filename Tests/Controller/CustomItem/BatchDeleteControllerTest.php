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
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Translation\TranslatorInterface;
use MauticPlugin\CustomObjectsBundle\Model\CustomItemModel;
use MauticPlugin\CustomObjectsBundle\Provider\CustomItemPermissionProvider;
use MauticPlugin\CustomObjectsBundle\Controller\CustomItem\BatchDeleteController;
use MauticPlugin\CustomObjectsBundle\Provider\CustomItemRouteProvider;
use MauticPlugin\CustomObjectsBundle\Provider\CustomItemSessionProvider;
use MauticPlugin\CustomObjectsBundle\Exception\NotFoundException;
use MauticPlugin\CustomObjectsBundle\Tests\Controller\ControllerDependenciesTrait;
use MauticPlugin\CustomObjectsBundle\Exception\ForbiddenException;
use MauticPlugin\CustomObjectsBundle\Entity\CustomItem;

class BatchDeleteControllerTest extends \PHPUnit_Framework_TestCase
{
    use ControllerDependenciesTrait;
    
    private $requestStack;
    private $customItemModel;
    private $sessionprovider;
    private $translator;
    private $permissionProvider;
    private $routeProvider;
    private $request;

    /**
     * @var BatchDeleteController
     */
    private $batchDeleteController;

    protected function setUp()
    {
        parent::setUp();

        $this->requestStack          = $this->createMock(RequestStack::class);
        $this->customItemModel       = $this->createMock(CustomItemModel::class);
        $this->sessionprovider       = $this->createMock(CustomItemSessionProvider::class);
        $this->translator            = $this->createMock(TranslatorInterface::class);
        $this->permissionProvider    = $this->createMock(CustomItemPermissionProvider::class);
        $this->routeProvider         = $this->createMock(CustomItemRouteProvider::class);
        $this->request               = $this->createMock(Request::class);
        $this->batchDeleteController = new BatchDeleteController(
            $this->requestStack,
            $this->customItemModel,
            $this->sessionprovider,
            $this->translator,
            $this->permissionProvider,
            $this->routeProvider 
        );

        $this->addSymfonyDependencies($this->batchDeleteController);
        
        $this->request->method('isXmlHttpRequest')->willReturn(true);
    }

    public function testDeleteActionIfCustomItemNotFound()
    {
        $this->request->expects($this->at(0))
            ->method('get')
            ->with('ids', '[]')
            ->willReturn('[13, 14]');

        $this->customItemModel->expects($this->exactly(2))
            ->method('fetchEntity')
            ->will($this->throwException(new NotFoundException()));

        $this->customItemModel->expects($this->never())
            ->method('delete');

        $this->translator->expects($this->once())
            ->method('trans')
            ->with('custom.item.error.items.not.found', ['%ids%' => '13,14'], 'flashes')
            ->willReturn('some translation');
        
        $this->sessionprovider->expects($this->once())
            ->method('addFlash')
            ->with('some translation', 'error');

        $this->batchDeleteController->deleteAction(33);
    }

    public function testDeleteActionIfCustomItemForbidden()
    {
        $this->request->expects($this->at(0))
            ->method('get')
            ->with('ids', '[]')
            ->willReturn('[13, 14]');

        $this->customItemModel->expects($this->exactly(2))
            ->method('fetchEntity')
            ->willReturn($this->createMock(CustomItem::class));

        $this->permissionProvider->expects($this->exactly(2))
            ->method('canDelete')
            ->will($this->throwException(new ForbiddenException('delete')));

        $this->customItemModel->expects($this->never())
            ->method('delete');

        $this->translator->expects($this->once())
            ->method('trans')
            ->with('custom.item.error.items.denied', ['%ids%' => '13,14'], 'flashes')
            ->willReturn('some translation');
        
        $this->sessionprovider->expects($this->once())
            ->method('addFlash')
            ->with('some translation', 'error');

        $this->batchDeleteController->deleteAction(33);
    }

    public function testDeleteAction()
    {
        $customItem13 = $this->createMock(CustomItem::class);
        $customItem14 = $this->createMock(CustomItem::class);

        $this->request->expects($this->at(0))
            ->method('get')
            ->with('ids', '[]')
            ->willReturn('[13, 14]');

        $this->customItemModel->expects($this->at(0))
            ->method('fetchEntity')
            ->with(13)
            ->willReturn($customItem13);

        $this->customItemModel->expects($this->at(1))
            ->method('delete')
            ->with($customItem13);
            
        $this->customItemModel->expects($this->at(2))
            ->method('fetchEntity')
            ->with(14)
            ->willReturn($customItem14);

        $this->customItemModel->expects($this->at(3))
            ->method('delete')
            ->with($customItem14);

        $this->translator->expects($this->once())
            ->method('trans')
            ->with('mautic.core.notice.batch_deleted', ['%count%' => 2], 'flashes')
            ->willReturn('some translation');
        
        $this->sessionprovider->expects($this->once())
            ->method('addFlash')
            ->with('some translation', 'notice');

        $this->sessionprovider->expects($this->once())
            ->method('getPage')
            ->willReturn(3);

        $this->routeProvider->expects($this->once())
            ->method('buildListRoute')
            ->with(33, 3);

        $this->batchDeleteController->deleteAction(33);
    }
}
