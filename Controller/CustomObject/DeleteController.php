<?php

declare(strict_types=1);

namespace MauticPlugin\CustomObjectsBundle\Controller\CustomObject;

use Mautic\CoreBundle\Controller\CommonController;
use Mautic\CoreBundle\Service\FlashBag;
use MauticPlugin\CustomObjectsBundle\CustomObjectEvents;
use MauticPlugin\CustomObjectsBundle\Event\CustomObjectEvent;
use MauticPlugin\CustomObjectsBundle\Exception\ForbiddenException;
use MauticPlugin\CustomObjectsBundle\Exception\InUseException;
use MauticPlugin\CustomObjectsBundle\Exception\NotFoundException;
use MauticPlugin\CustomObjectsBundle\Model\CustomObjectModel;
use MauticPlugin\CustomObjectsBundle\Provider\CustomObjectPermissionProvider;
use MauticPlugin\CustomObjectsBundle\Provider\SessionProviderFactory;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Response;
use Doctrine\Persistence\ManagerRegistry;
use Mautic\CoreBundle\Factory\ModelFactory;
use Mautic\CoreBundle\Helper\UserHelper;
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\CoreBundle\Translation\Translator;
use Mautic\CoreBundle\Security\Permissions\CorePermissions;
use Symfony\Component\HttpFoundation\RequestStack;
use Mautic\CoreBundle\Factory\MauticFactory;

class DeleteController extends CommonController
{
    public function __construct(
        ManagerRegistry $doctrine,
        MauticFactory $mauticFactory,
        ModelFactory $modelFactory,
        UserHelper $userHelper,
        CoreParametersHelper $coreParametersHelper,
        EventDispatcherInterface $dispatcher,
        Translator $translator,
        private FlashBag $flashBag,
        private RequestStack $requestStack,
        CorePermissions $security,
        private CustomObjectModel $customObjectModel,
        private SessionProviderFactory $sessionProviderFactory,
        private CustomObjectPermissionProvider $permissionProvider,
        private EventDispatcherInterface $eventDispatcher
    ) {
        parent::__construct($doctrine, $mauticFactory, $modelFactory, $userHelper, $coreParametersHelper, $dispatcher, $translator, $flashBag, $requestStack, $security);
    }

    public function deleteAction(int $objectId): Response
    {
        $controller = 'MauticPlugin\CustomObjectsBundle\Controller\CustomObject\ListController::listAction';
        $page       = [
            'page' => $this->sessionProviderFactory->createObjectProvider()->getPage(),
        ];

        try {
            $customObject          = $this->customObjectModel->fetchEntity($objectId);
            $translationParameters = [
                '%name%' => $customObject->getName(),
                '%id%'   => $customObject->getId(),
            ];

            $this->permissionProvider->canDelete($customObject);
            $this->customObjectModel->checkIfTheCustomObjectIsUsedInSegmentFilters($customObject);
        } catch (NotFoundException $e) {
            return $this->notFound($e->getMessage());
        } catch (ForbiddenException $e) {
            return $this->accessDenied(false, $e->getMessage());
        } catch (InUseException $exception) {
            $segments = [];
            foreach ($exception->getSegmentList() as $relatedSegment) {
                $segments[] = sprintf('"%s" (%d)', $relatedSegment->getName(), $relatedSegment->getId());
            }

            $translationParameters['%segments%'] = implode(', ', $segments);
            $this->flashBag->add('custom.object.error.used.in.segments', $translationParameters, FlashBag::LEVEL_ERROR);

            return $this->forward(
                $controller,
                $page
            );
        }

        $customObjectEvent = new CustomObjectEvent($customObject);
        $customObjectEvent->setFlashBag($this->flashBag);
        $this->eventDispatcher->dispatch(new CustomObjectEvent($customObject), CustomObjectEvents::ON_CUSTOM_OBJECT_USER_PRE_DELETE);

        return $this->forward(
            $controller,
            $page
        );
    }
}
