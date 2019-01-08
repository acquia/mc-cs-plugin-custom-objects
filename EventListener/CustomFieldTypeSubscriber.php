<?php

/*
 * @copyright   2018 Mautic, Inc. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.com
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\CustomObjectsBundle\EventListener;

use Mautic\CoreBundle\EventListener\CommonSubscriber;
use MauticPlugin\CustomObjectsBundle\CustomFieldEvents;
use MauticPlugin\CustomObjectsBundle\Event\CustomFieldTypeEvent;
use MauticPlugin\CustomObjectsBundle\CustomFieldType\IntType;
use MauticPlugin\CustomObjectsBundle\CustomFieldType\TextType;
use Symfony\Component\Translation\TranslatorInterface;

class CustomFieldTypeSubscriber extends CommonSubscriber
{
    /**
     * @param TranslatorInterface $translator
     */
    public function __construct(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }

    public static function getSubscribedEvents()
    {
        return [
            CustomFieldEvents::MAKE_FIELD_TYPE_LIST => ['makeFieldTypeList', 0],
        ];
    }

    /**
     * @todo add more field types
     * 
     * @param CustomFieldTypeEvent $event
     */
    public function makeFieldTypeList(CustomFieldTypeEvent $event)
    {
        $event->addCustomFieldType(new IntType($this->translator->trans('custom.field.type.int')));
        $event->addCustomFieldType(new TextType($this->translator->trans('custom.field.type.text')));
    }
}
