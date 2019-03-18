<?php

declare(strict_types=1);

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
use MauticPlugin\CustomObjectsBundle\CustomFieldType\CheckboxGroupType;
use MauticPlugin\CustomObjectsBundle\CustomFieldType\CountryListType;
use MauticPlugin\CustomObjectsBundle\CustomFieldType\DateTimeType;
use MauticPlugin\CustomObjectsBundle\CustomFieldType\DateType;
use MauticPlugin\CustomObjectsBundle\CustomFieldType\EmailType;
use MauticPlugin\CustomObjectsBundle\CustomFieldType\HiddenType;
use MauticPlugin\CustomObjectsBundle\CustomFieldType\HtmlAreaType;
use MauticPlugin\CustomObjectsBundle\CustomFieldType\RadioGroupType;
use MauticPlugin\CustomObjectsBundle\CustomFieldType\SelectType;
use MauticPlugin\CustomObjectsBundle\CustomFieldType\PhoneType;
use MauticPlugin\CustomObjectsBundle\CustomFieldType\UrlType;
use MauticPlugin\CustomObjectsBundle\Event\CustomFieldTypeEvent;
use MauticPlugin\CustomObjectsBundle\CustomFieldType\IntType;
use MauticPlugin\CustomObjectsBundle\CustomFieldType\TextType;
use MauticPlugin\CustomObjectsBundle\CustomFieldType\TextareaType;
use Symfony\Component\Translation\TranslatorInterface;
use MauticPlugin\CustomObjectsBundle\CustomFieldType\MultiselectType;

class CustomFieldTypeSubscriber extends CommonSubscriber
{
    /**
     * @var TranslatorInterface
     */
    protected $translator;

    /**
     * @param TranslatorInterface $translator
     */
    public function __construct(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }

    /**
     * @return mixed[]
     */
    public static function getSubscribedEvents(): array
    {
        return [
            CustomFieldEvents::MAKE_FIELD_TYPE_LIST => ['makeFieldTypeList', 0],
        ];
    }

    /**
     * @param CustomFieldTypeEvent $event
     */
    public function makeFieldTypeList(CustomFieldTypeEvent $event): void
    {
        $event->addCustomFieldType(new CheckboxGroupType($this->translator->trans('custom.field.type.checkbox_group')));
        $event->addCustomFieldType(new CountryListType($this->translator->trans('custom.field.type.country_list')));
        $event->addCustomFieldType(new DateType($this->translator->trans('custom.field.type.date')));
        $event->addCustomFieldType(new DateTimeType($this->translator->trans('custom.field.type.datetime')));
        $event->addCustomFieldType(new EmailType($this->translator->trans('custom.field.type.email')));
        $event->addCustomFieldType(new HiddenType($this->translator->trans('custom.field.type.hidden')));
        $event->addCustomFieldType(new HtmlAreaType($this->translator->trans('custom.field.type.html_area')));
        $event->addCustomFieldType(new IntType($this->translator->trans('custom.field.type.int')));
        $event->addCustomFieldType(new PhoneType($this->translator->trans('custom.field.type.phone')));
        $event->addCustomFieldType(new RadioGroupType($this->translator->trans('custom.field.type.radio_group')));
        $event->addCustomFieldType(new SelectType($this->translator->trans('custom.field.type.select')));
        $event->addCustomFieldType(new MultiselectType($this->translator->trans('custom.field.type.multiselect')));
        $event->addCustomFieldType(new TextType($this->translator->trans('custom.field.type.text')));
        $event->addCustomFieldType(new TextareaType($this->translator->trans('custom.field.type.textarea')));
        $event->addCustomFieldType(new UrlType($this->translator->trans('custom.field.type.url')));
    }
}
