<?php

declare(strict_types=1);

namespace MauticPlugin\CustomObjectsBundle\EventListener;

use MauticPlugin\CustomObjectsBundle\CustomObjectEvents;
use MauticPlugin\CustomObjectsBundle\Entity\CustomField;
use MauticPlugin\CustomObjectsBundle\Entity\CustomField\Params;
use MauticPlugin\CustomObjectsBundle\Event\CustomObjectEvent;
use MauticPlugin\CustomObjectsBundle\Model\CustomFieldOptionModel;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * CustomField entity lifecycle ends here.
 */
class CustomFieldPreSaveSubscriber implements EventSubscriberInterface
{
    /**
     * @var CustomFieldOptionModel
     */
    private $customFieldOptionModel;

    /**
     * CustomFieldPreSaveSubscriber constructor.
     */
    public function __construct(CustomFieldOptionModel $customFieldOptionModel)
    {
        $this->customFieldOptionModel = $customFieldOptionModel;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        // Doctrine event preFlush could not be used,
        // because it does not contain entity itself
        return [
            CustomObjectEvents::ON_CUSTOM_OBJECT_PRE_SAVE => 'preSave',
        ];
    }

    public function preSave(CustomObjectEvent $event): void
    {
        $event->getCustomObject()->getCustomFields()->map(
            function (CustomField $customField): void {
                if ($customField->getId()) {
                    $this->customFieldOptionModel->deleteByCustomFieldId($customField->getId());
                }

                $params = $customField->getParams();

                if ($params instanceof Params) {
                    $customField->setParams($params->__toArray());
                } else {
                    $customField->setParams($params);
                }
            }
        );
    }
}
