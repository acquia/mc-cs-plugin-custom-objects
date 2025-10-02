<?php

declare(strict_types=1);

namespace MauticPlugin\CustomObjectsBundle\Tests\Unit\Controller\CustomItem;

use Mautic\CoreBundle\Service\FlashBag;
use MauticPlugin\CustomObjectsBundle\Controller\CustomItem\LookupController;
use MauticPlugin\CustomObjectsBundle\DTO\TableConfig;
use MauticPlugin\CustomObjectsBundle\Exception\ForbiddenException;
use MauticPlugin\CustomObjectsBundle\Model\CustomItemModel;
use MauticPlugin\CustomObjectsBundle\Provider\CustomItemPermissionProvider;
use MauticPlugin\CustomObjectsBundle\Tests\Unit\Controller\ControllerTestCase;
use Symfony\Component\HttpFoundation\Request;

class LookupControllerTest extends ControllerTestCase
{
    private const OBJECT_ID = 22;

    private $customItemModel;

    private $permissionProvider;

    /**
     * @var LookupController
     */
    private $lookupController;

    protected function setUp(): void
    {
        parent::setUp();

        $this->customItemModel    = $this->createMock(CustomItemModel::class);
        $this->permissionProvider = $this->createMock(CustomItemPermissionProvider::class);
        $this->request            = $this->createMock(Request::class);
        $this->lookupController   = new LookupController();

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

        $this->lookupController->listAction(
            $this->request,
            $this->customItemModel,
            $this->permissionProvider,
            $this->flashBag,
            self::OBJECT_ID
        );
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
                $this->assertSame(15, $tableConfig->getLimit());
                $this->assertSame(0, $tableConfig->getOffset());
                $this->assertSame('CustomItem.name', $tableConfig->getOrderBy());
                $this->assertSame('ASC', $tableConfig->getOrderDirection());
                $this->assertSame('', $tableConfig->getParameter('search'));
                $this->assertSame(self::OBJECT_ID, $tableConfig->getParameter('customObjectId'));
                $this->assertSame('', $tableConfig->getParameter('filterEntityType'));
                $this->assertSame(0, $tableConfig->getParameter('filterEntityId'));

                return true;
            }));

        $this->lookupController->listAction(
            $this->request,
            $this->customItemModel,
            $this->permissionProvider,
            $this->flashBag,
            self::OBJECT_ID
        );
    }

    public function testListActionForContactEntity(): void
    {
        $this->permissionProvider->expects($this->once())
            ->method('canViewAtAll');

        $this->flashBag->expects($this->never())
            ->method('add');

        $this->request->method('get')->will($this->returnValueMap([
            ['filterEntityId', null, 45],
            ['filterEntityType', null, 'contact'],
        ]));

        $this->customItemModel->expects($this->once())
            ->method('getLookupData')
            ->with($this->callback(function (TableConfig $tableConfig) {
                $this->assertSame(15, $tableConfig->getLimit());
                $this->assertSame(0, $tableConfig->getOffset());
                $this->assertSame('CustomItem.name', $tableConfig->getOrderBy());
                $this->assertSame('ASC', $tableConfig->getOrderDirection());
                $this->assertSame('', $tableConfig->getParameter('search'));
                $this->assertSame(self::OBJECT_ID, $tableConfig->getParameter('customObjectId'));
                $this->assertSame('contact', $tableConfig->getParameter('filterEntityType'));
                $this->assertSame(45, $tableConfig->getParameter('filterEntityId'));

                return true;
            }));

        $this->lookupController->listAction(
            $this->request,
            $this->customItemModel,
            $this->permissionProvider,
            $this->flashBag,
            self::OBJECT_ID
        );
    }
}
