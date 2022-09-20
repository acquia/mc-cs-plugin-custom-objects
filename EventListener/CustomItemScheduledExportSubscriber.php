<?php

declare(strict_types=1);

namespace MauticPlugin\CustomObjectsBundle\EventListener;

use MauticPlugin\CustomObjectsBundle\CustomItemEvents;
use MauticPlugin\CustomObjectsBundle\Event\CustomItemExportSchedulerEvent;
use MauticPlugin\CustomObjectsBundle\Model\CustomItemExportSchedulerModel;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class CustomItemScheduledExportSubscriber implements EventSubscriberInterface
{
    private CustomItemExportSchedulerModel $customItemExportSchedulerModel;

    public function __construct(CustomItemExportSchedulerModel $customItemExportSchedulerModel)
    {
        $this->customItemExportSchedulerModel = $customItemExportSchedulerModel;
    }

    /**
     * @return array<string, string>
     */
    public static function getSubscribedEvents(): array
    {
        return [
            CustomItemEvents::CUSTOM_ITEM_PREPARE_EXPORT_FILE => 'onCustomItemExportScheduled',
            CustomItemEvents::CUSTOM_ITEM_MAIL_EXPORT_FILE    => 'sendEmail',
            CustomItemEvents::POST_EXPORT_MAIL_SENT           => 'onExportEmailSent',
        ];
    }

    public function onCustomItemExportScheduled(CustomItemExportSchedulerEvent $event): void
    {
        $customItemExportScheduler = $event->getCustomItemExportScheduler();
        $filePath                  = $this->customItemExportSchedulerModel->processDataAndGetExportFilePath($customItemExportScheduler);
        $event->setFilePath($filePath);
    }

    public function sendEmail(CustomItemExportSchedulerEvent $event): void
    {
        $customItemExportScheduler = $event->getCustomItemExportScheduler();
        $this->customItemExportSchedulerModel->sendEmail($customItemExportScheduler, $event->getFilePath());
    }

    public function onExportEmailSent(CustomItemExportSchedulerEvent $event): void
    {
        $customItemExportScheduler = $event->getCustomItemExportScheduler();
        $this->customItemExportSchedulerModel->deleteEntity($customItemExportScheduler);
    }
}
