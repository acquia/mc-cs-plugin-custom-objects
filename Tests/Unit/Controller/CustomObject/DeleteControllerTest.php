<?php

declare(strict_types=1);

namespace MauticPlugin\CustomObjectsBundle\Tests\Unit\Controller\CustomObject;

use Doctrine\Common\Collections\ArrayCollection;
use Mautic\CoreBundle\Service\FlashBag;
use Mautic\LeadBundle\Entity\LeadList;
use MauticPlugin\CustomObjectsBundle\Controller\CustomObject\DeleteController;
use MauticPlugin\CustomObjectsBundle\Entity\CustomObject;
use MauticPlugin\CustomObjectsBundle\Exception\ForbiddenException;
use MauticPlugin\CustomObjectsBundle\Exception\InUseException;
use MauticPlugin\CustomObjectsBundle\Exception\NotFoundException;
use MauticPlugin\CustomObjectsBundle\Model\CustomObjectModel;
use MauticPlugin\CustomObjectsBundle\Provider\CustomObjectPermissionProvider;
use MauticPlugin\CustomObjectsBundle\Provider\SessionProvider;
use MauticPlugin\CustomObjectsBundle\Provider\SessionProviderFactory;
use MauticPlugin\CustomObjectsBundle\Tests\Unit\Controller\ControllerTestCase;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Contracts\Translation\TranslatorInterface;

class DeleteControllerTest extends ControllerTestCase
{
    private const OBJECT_ID = 33;

    private $customObjectModel;
    private $sessionProvider;
    private $flashBag;
    private $permissionProvider;

    /**
     * @var EventDispatcherInterface
     */
    private $eventDispatcher;

    /**
     * @var DeleteController
     */
    private $deleteController;

    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * @var int
     */
    private $leadListIndex;

    protected function setUp(): void
    {
        parent::setUp();

        $sessionProviderFactory   = $this->createMock(SessionProviderFactory::class);
        $this->customObjectModel  = $this->createMock(CustomObjectModel::class);
        $this->sessionProvider    = $this->createMock(SessionProvider::class);
        $this->flashBag           = $this->createMock(FlashBag::class);
        $this->permissionProvider = $this->createMock(CustomObjectPermissionProvider::class);
        $this->request            = $this->createMock(Request::class);
        $this->eventDispatcher    = $this->createMock(EventDispatcherInterface::class);
        $this->deleteController   = new DeleteController(
            $this->customObjectModel,
            $sessionProviderFactory,
            $this->flashBag,
            $this->permissionProvider,
            $this->eventDispatcher
        );

        $this->addSymfonyDependencies($this->deleteController);

        $this->request->method('isXmlHttpRequest')->willReturn(true);
        $this->request->method('getRequestUri')->willReturn('https://a.b');
        $sessionProviderFactory->method('createObjectProvider')->willReturn($this->sessionProvider);

        $this->translator = $this->createMock(TranslatorInterface::class);
        $this->deleteController->setTranslator($this->translator);

        $this->leadListIndex = 1;
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

        $this->eventDispatcher->expects($this->once())
            ->method('dispatch');

        $this->sessionProvider->expects($this->once())
            ->method('getPage')
            ->willReturn(3);

        $this->deleteController->deleteAction(self::OBJECT_ID);
    }

    public function testThatItDisplaysErrorMessageIfThereAreRelatedSegments(): void
    {
        $customObject = $this->createMock(CustomObject::class);

        $customObject->method('getId')->willReturn(self::OBJECT_ID);

        $this->customObjectModel->expects($this->once())
            ->method('fetchEntity')
            ->with(self::OBJECT_ID)
            ->willReturn($customObject);

        $this->eventDispatcher->expects($this->never())
            ->method('dispatch');

        $this->flashBag->expects($this->once())
            ->method('add');

        $this->sessionProvider->expects($this->once())
            ->method('getPage')
            ->willReturn(3);

        $segments       = $this->createSegments(2);
        $inUseException = new InUseException();
        $inUseException->setSegmentList($segments->toArray());

        $this->customObjectModel->expects($this->once())
            ->method('checkIfTheCustomObjectIsUsedInSegmentFilters')
            ->willThrowException($inUseException);

        $this->deleteController->deleteAction(self::OBJECT_ID);
    }

    private function createSegments(int $quantity): ArrayCollection
    {
        $segments = new ArrayCollection();
        for ($i = 1; $i <= $quantity; ++$i) {
            $segment = new LeadList();
            $segment->setName('Segment '.$i);
            $segments->add($segment);
        }

        return $segments;
    }
}
