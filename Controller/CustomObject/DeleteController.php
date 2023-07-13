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

class DeleteController extends CommonController
{
    public function deleteAction(
        SessionProviderFactory $sessionProviderFactory,
        CustomObjectModel $customObjectModel,
        FlashBag $flashBag,
        CustomObjectPermissionProvider $permissionProvider,
        EventDispatcherInterface $eventDispatcher,
        int $objectId
    ): Response {
        $controller = 'MauticPlugin\CustomObjectsBundle\Controller\CustomObject\ListController:listAction';
        $page       = [
            'page' => $sessionProviderFactory->createObjectProvider()->getPage(),
        ];

        try {
            $customObject          = $customObjectModel->fetchEntity($objectId);
            $translationParameters = [
                '%name%' => $customObject->getName(),
                '%id%'   => $customObject->getId(),
            ];

            $permissionProvider->canDelete($customObject);
            $customObjectModel->checkIfTheCustomObjectIsUsedInSegmentFilters($customObject);
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
            $flashBag->add('custom.object.error.used.in.segments', $translationParameters, FlashBag::LEVEL_ERROR);

            return $this->forward(
                $controller,
                $page
            );
        }

        $customObjectEvent = new CustomObjectEvent($customObject);
        $customObjectEvent->setFlashBag($flashBag);
        $eventDispatcher->dispatch(CustomObjectEvents::ON_CUSTOM_OBJECT_USER_PRE_DELETE, $customObjectEvent);

        return $this->forward(
            $controller,
            $page
        );
    }
}
