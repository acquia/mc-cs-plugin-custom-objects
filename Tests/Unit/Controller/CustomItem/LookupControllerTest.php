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

use MauticPlugin\CustomObjectsBundle\Model\CustomItemModel;
use MauticPlugin\CustomObjectsBundle\Provider\CustomItemPermissionProvider;
use MauticPlugin\CustomObjectsBundle\Controller\CustomItem\LookupController;
use MauticPlugin\CustomObjectsBundle\Tests\Unit\Controller\ControllerTestCase;
use Symfony\Component\HttpFoundation\RequestStack;
use MauticPlugin\CustomObjectsBundle\Exception\ForbiddenException;
use Mautic\CoreBundle\Service\FlashBag;
use Symfony\Component\HttpFoundation\Request;
use MauticPlugin\CustomObjectsBundle\DTO\TableConfig;

class LookupControllerTest extends ControllerTestCase
{
    private const OBJECT_ID = 22;

    private $customItemModel;
    private $permissionProvider;
    private $flashBag;

    /**
     * @var LookupController
     */
    private $lookupController;

    protected function setUp(): void
    {
        parent::setUp();

        $this->customItemModel    = $this->createMock(CustomItemModel::class);
        $this->requestStack       = $this->createMock(RequestStack::class);
        $this->permissionProvider = $this->createMock(CustomItemPermissionProvider::class);
        $this->flashBag           = $this->createMock(FlashBag::class);
        $this->request            = $this->createMock(Request::class);
        $this->lookupController   = new LookupController(
            $this->requestStack,
            $this->customItemModel,
            $this->permissionProvider,
            $this->flashBag
        );

        $this->addSymfonyDependencies($this->lookupController);
    }

    public function testListActionIfForbidden(): void
    {
        $this->permissionProvider->expects($this->once())
            ->method('canViewAtAll')
            ->will($this->throwException(new ForbiddenException('view')));

        $this->flashBag->expects($this->once())
            ->method('add')
            ->with('You do not have permission to view', [], FlashBag::LEVEL_ERROR);

        $this->customItemModel->expects($this->never())
            ->method('getLookupData');

        $this->lookupController->listAction(self::OBJECT_ID);
    }

    public function testListAction(): void
    {
        $this->permissionProvider->expects($this->once())
            ->method('canViewAtAll');

        $this->flashBag->expects($this->never())
            ->method('add');

        $this->customItemModel->expects($this->once())
            ->method('getLookupData')
            ->with($this->callback(function (TableConfig $tableConfig) {
                $this->assertSame(10, $tableConfig->getLimit());
                $this->assertSame(0, $tableConfig->getOffset());
                $this->assertSame('CustomItem.name', $tableConfig->getOrderBy());
                $this->assertSame('ASC', $tableConfig->getOrderDirection());
                $this->assertSame('', $tableConfig->getParameter('search'));
                $this->assertSame(self::OBJECT_ID, $tableConfig->getParameter('customObjectId'));
                $this->assertSame('', $tableConfig->getParameter('filterEntityType'));
                $this->assertSame(0, $tableConfig->getParameter('filterEntityId'));

                return true;
            }));

        $this->lookupController->listAction(self::OBJECT_ID);
    }

    public function testListActionForContactEntity(): void
    {
        $this->permissionProvider->expects($this->once())
            ->method('canViewAtAll');

        $this->flashBag->expects($this->never())
            ->method('add');

        $this->request->method('get')->will($this->returnValueMap([
            ['filterEntityId', null, false, 45],
            ['filterEntityType', null, false, 'contact'],
        ]));

        $this->customItemModel->expects($this->once())
            ->method('getLookupData')
            ->with($this->callback(function (TableConfig $tableConfig) {
                $this->assertSame(10, $tableConfig->getLimit());
                $this->assertSame(0, $tableConfig->getOffset());
                $this->assertSame('CustomItem.name', $tableConfig->getOrderBy());
                $this->assertSame('ASC', $tableConfig->getOrderDirection());
                $this->assertSame('', $tableConfig->getParameter('search'));
                $this->assertSame(self::OBJECT_ID, $tableConfig->getParameter('customObjectId'));
                $this->assertSame('contact', $tableConfig->getParameter('filterEntityType'));
                $this->assertSame(45, $tableConfig->getParameter('filterEntityId'));

                return true;
            }));

        $this->lookupController->listAction(self::OBJECT_ID);
    }
}
