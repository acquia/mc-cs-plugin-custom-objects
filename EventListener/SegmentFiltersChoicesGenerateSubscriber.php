<?php

/*
 * @copyright   2019 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\CustomObjectsBundle\EventListener;

use Doctrine\Common\Collections\Criteria;
use Mautic\LeadBundle\Entity\OperatorListTrait;
use Mautic\LeadBundle\Event\LeadListFiltersChoicesEvent;
use Mautic\LeadBundle\LeadEvents;
use MauticPlugin\CustomObjectsBundle\Entity\CustomField;
use MauticPlugin\CustomObjectsBundle\Entity\CustomObject;
use MauticPlugin\CustomObjectsBundle\Repository\CustomFieldRepository;
use MauticPlugin\CustomObjectsBundle\Repository\CustomItemRepository;
use MauticPlugin\CustomObjectsBundle\Repository\CustomObjectRepository;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Translation\Loader\ArrayLoader;
use Symfony\Component\Translation\TranslatorInterface;

class SegmentFiltersChoicesGenerateSubscriber implements EventSubscriberInterface
{
    use OperatorListTrait;

    /**
     * @var CustomObjectRepository
     */
    private $customObjectRepository;
    /**
     * @var CustomFieldRepository
     */
    private $customFieldRepository;
    /**
     * @var CustomItemRepository
     */
    private $customItemRepository;
    /**
     * @var TranslatorInterface
     */
    private $translator;

    public function __construct(
        CustomObjectRepository $customObjectRepository,
        CustomFieldRepository $customFieldRepository,
        CustomItemRepository $customItemRepository,
        TranslatorInterface $translator
    )
    {

        $this->customObjectRepository = $customObjectRepository;
        $this->customFieldRepository  = $customFieldRepository;
        $this->customItemRepository   = $customItemRepository;
        $this->translator             = $translator;
    }

    /**
     * @return array
     */
    public static function getSubscribedEvents(): array
    {
        return [LeadEvents::LIST_FILTERS_CHOICES_ON_GENERATE => 'onGenerateSegmentFilters'];
    }

    public function onGenerateSegmentFilters(LeadListFiltersChoicesEvent $event)
    {
        $criteria     = new Criteria(Criteria::expr()->eq('isPublished', 1));
        $translations = [];

        $this->customObjectRepository->matching($criteria)->forAll(
            function (int $index, CustomObject $customObject) use ($event, &$translations) {
                $translations['mautic.lead.custom_object_' . $customObject->getId()] = $customObject->getNamePlural();
                foreach ($customObject->getFields()->getIterator() as $customField) {
                    $event->addChoice(
                        'custom_object', // . $customObject->getId(), //$event->getTranslator()->trans('custom.object.menu.title'),
                        'cmf_' . $customField->getId(),
                        [
                            'label'      => $customField->getCustomObject()->getName() . " : " . $customField->getLabel(),
                            'properties' => ['type' => $customField->getType()],
                            'operators'  => $this->getOperatorsForFieldType($customField->getType()),
                            'object'     => $customField->getId(),
                        ]
                    );
                };
            }
        );
    }
}