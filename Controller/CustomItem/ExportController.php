<?php

declare(strict_types=1);

namespace MauticPlugin\CustomObjectsBundle\Controller\CustomItem;

use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Mautic\CoreBundle\Controller\AbstractFormController;
use MauticPlugin\CustomObjectsBundle\CustomItemEvents;
use MauticPlugin\CustomObjectsBundle\Event\CustomItemExportSchedulerEvent;
use MauticPlugin\CustomObjectsBundle\Exception\ForbiddenException;
use MauticPlugin\CustomObjectsBundle\Model\CustomItemExportSchedulerModel;
use MauticPlugin\CustomObjectsBundle\Provider\CustomItemPermissionProvider;
use Symfony\Component\HttpFoundation\File\Exception\FileNotFoundException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class ExportController extends AbstractFormController
{
    /**
     * @throws ORMException
     * @throws OptimisticLockException
     * @throws ForbiddenException
     */
    public function exportAction(
        CustomItemPermissionProvider $permissionProvider,
        CustomItemExportSchedulerModel $model,
        int $object
    ): Response {
        $permissionProvider->canCreate($object);

        $customItemExportScheduler = $model->saveEntity($object);

        $this->dispatcher->dispatch(
            new CustomItemExportSchedulerEvent($customItemExportScheduler),
            CustomItemEvents::ON_CUSTOM_ITEM_SCHEDULE_EXPORT
        );

        $this->addFlash('custom.item.export.being.prepared', ['%user_email%' => $this->user->getEmail()]);
        $response['message'] = 'Custom Item export scheduled.';
        $response['flashes'] = $this->getFlashContent();

        return new JsonResponse($response);
    }

    public function downloadExportAction(CustomItemExportSchedulerModel $model, string $fileName): Response
    {
        try {
            return $model->getExportFileToDownload($fileName);
        } catch (FileNotFoundException $exception) {
            return $this->notFound();
        }
    }
}
