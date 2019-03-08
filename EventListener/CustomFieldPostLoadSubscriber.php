<?php

declare(strict_types=1);

/*
* @copyright   2019 Mautic, Inc. All rights reserved
* @author      Mautic, Inc.
*
* @link        https://mautic.com
*
* @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
*/

namespace MauticPlugin\CustomObjectsBundle\EventListener;

use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\Events;
use Doctrine\ORM\Event\LifecycleEventArgs;
use MauticPlugin\CustomObjectsBundle\Entity\CustomField;
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

    /**
     * @param CustomFieldTypeProvider $customFieldTypeProvider
     */
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
     * @param LifecycleEventArgs $args
     *
     * @throws \MauticPlugin\CustomObjectsBundle\Exception\NotFoundException
     */
    public function postLoad(LifecycleEventArgs $args): void
    {
        $customField = $args->getObject();

        if (!$customField instanceof CustomField) {
            return;
        }

        $customField->setTypeObject($this->customFieldTypeProvider->getType($customField->getType()));
        $customField->setParams(new CustomField\Params($customField->getParams()));
    }
}