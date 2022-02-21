<?php

declare(strict_types=1);

namespace MauticPlugin\CustomObjectsBundle\EventListener;

use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Events;
use MauticPlugin\CustomObjectsBundle\Entity\CustomField;
use MauticPlugin\CustomObjectsBundle\Exception\NotFoundException;
use MauticPlugin\CustomObjectsBundle\Provider\CustomFieldTypeProvider;

/**
 * CustomField entity lifecycle starts here.
 */
class CustomFieldPostLoadSubscriber implements EventSubscriber
{
    /**
     * @var CustomFieldTypeProvider
     */
    private $customFieldTypeProvider;

    public function __construct(CustomFieldTypeProvider $customFieldTypeProvider)
    {
        $this->customFieldTypeProvider = $customFieldTypeProvider;
    }

    /**
     * @return mixed[]
     */
    public function getSubscribedEvents(): array
    {
        return [
            Events::postLoad,
        ];
    }

    /**
     * @throws NotFoundException
     */
    public function postLoad(LifecycleEventArgs $args): void
    {
        $customField = $args->getObject();

        if (!$customField instanceof CustomField) {
            return;
        }

        $type = $customField->getType();

        $customField->setTypeObject($this->customFieldTypeProvider->getType($type));

        if (is_array($customField->getParams())) {
            $customField->setParams(new CustomField\Params($customField->getParams()));
        }
    }
}
