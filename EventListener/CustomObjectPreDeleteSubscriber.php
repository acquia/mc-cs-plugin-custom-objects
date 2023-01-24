<?php

declare(strict_types=1);

namespace MauticPlugin\CustomObjectsBundle\EventListener;

use MauticPlugin\CustomObjectsBundle\CustomObjectEvents;
use MauticPlugin\CustomObjectsBundle\Event\CustomObjectEvent;
use MauticPlugin\CustomObjectsBundle\Model\CustomObjectModel;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class CustomObjectPreDeleteSubscriber implements EventSubscriberInterface
{
    /**
     * @var CustomObjectModel
     */
    private $customObjectModel;

    /**
     * @var TranslatorInterface
     */
    private $translator;

    public function __construct(CustomObjectModel $customObjectModel, TranslatorInterface $translator)
    {
        $this->customObjectModel = $customObjectModel;
        $this->translator        = $translator;
    }

    public static function getSubscribedEvents()
    {
        return [
            CustomObjectEvents::ON_CUSTOM_OBJECT_USER_PRE_DELETE => 'preDelete',
        ];
    }

    public function preDelete(CustomObjectEvent $event): void
    {
        $customObject = $event->getCustomObject();
        $this->customObjectModel->delete($customObject);

        $flashBag = $event->getFlashBag();
        if (!$flashBag) {
            return;
        }

        $message = $this->translator->trans('mautic.core.notice.deleted', ['%name%' => $customObject->getName()], 'flashes');
        $flashBag->add($message);
    }
}
