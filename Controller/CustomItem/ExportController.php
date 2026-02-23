<?php

declare(strict_types=1);

namespace MauticPlugin\CustomObjectsBundle\Controller\CustomItem;

use Mautic\CoreBundle\Controller\AbstractFormController;
use MauticPlugin\CustomObjectsBundle\Event\CustomItemExportSchedulerEvent;
use MauticPlugin\CustomObjectsBundle\Model\CustomItemExportSchedulerModel;
use MauticPlugin\CustomObjectsBundle\Provider\CustomItemPermissionProvider;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\File\Exception\FileNotFoundException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Doctrine\Persistence\ManagerRegistry;
use Mautic\CoreBundle\Factory\ModelFactory;
use Mautic\CoreBundle\Helper\UserHelper;
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\CoreBundle\Translation\Translator;
use Mautic\CoreBundle\Security\Permissions\CorePermissions;
use Symfony\Component\HttpFoundation\RequestStack;
use Mautic\CoreBundle\Service\FlashBag;
use Mautic\CoreBundle\Factory\MauticFactory;

class ExportController extends AbstractFormController
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
        private CustomItemPermissionProvider $permissionProvider,
        private CustomItemExportSchedulerModel $model
    ) {
        parent::__construct($doctrine, $mauticFactory, $modelFactory, $userHelper, $coreParametersHelper, $dispatcher, $translator, $flashBag, $requestStack, $security);
    }

    /**
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     * @throws \MauticPlugin\CustomObjectsBundle\Exception\ForbiddenException
     */
    public function exportAction(int $object): Response
    {
        $this->permissionProvider->canCreate($object);

        $customItemExportScheduler = $this->model->saveEntity($object);

        /** @var EventDispatcherInterface $dispatcher */
        $this->dispatcher->dispatch(new CustomItemExportSchedulerEvent($customItemExportScheduler));

        $this->addFlashMessage('custom.item.export.being.prepared', ['%user_email%' => $this->user->getEmail()]);
        $response['message'] = 'Custom Item export scheduled.';
        $response['flashes'] = $this->getFlashContent();

        return new JsonResponse($response);
    }

    public function downloadExportAction(string $fileName): Response
    {
        try {
            return $this->model->getExportFileToDownload($fileName);
        } catch (FileNotFoundException $exception) {
            return $this->notFound();
        }
    }
}
