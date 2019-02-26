<?php

declare(strict_types=1);

/*
 * @copyright   2019 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\CustomObjectsBundle\EventListener;

use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\Event\LifecycleEventArgs;
use MauticPlugin\CustomObjectsBundle\Entity\CustomField;
use MauticPlugin\CustomObjectsBundle\Provider\CustomFieldTypeProvider;

class CustomFieldSubscriber implements EventSubscriber
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
     * @return array
     */
    public function getSubscribedEvents(): array
    {
        return ['postLoad'];
    }

    /**
     * @param LifecycleEventArgs $args
     *
     * @throws \MauticPlugin\CustomObjectsBundle\Exception\NotFoundException
     */
    public function postLoad(LifecycleEventArgs $args): void
    {
        $customField = $args->getObject();

        if ($customField instanceof CustomField) {
            $customField->setTypeObject($this->customFieldTypeProvider->getType($customField->getType()));
        }
    }
}
