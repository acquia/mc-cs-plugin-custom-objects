<?php
declare(strict_types=1);
/*
 * @copyright   2019 Mautic Contributors. All rights reserved
 * @author      Mautic Inc, Jan Kozak <galvani78@gmail.com>
 *
 * @link        http://mautic.com
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\CustomObjectsBundle\EventListener;

use Doctrine\Common\Collections\Criteria;
use Mautic\LeadBundle\Entity\OperatorListTrait;
use Mautic\LeadBundle\Event\LeadListFiltersChoicesEvent;
use Mautic\LeadBundle\LeadEvents;
use MauticPlugin\CustomObjectsBundle\Entity\CustomObject;
use MauticPlugin\CustomObjectsBundle\Repository\CustomObjectRepository;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Translation\TranslatorInterface;

/**
 * SegmentFiltersChoicesGenerateSubscriber
 *
 * @package MauticPlugin\CustomObjectsBundle\EventListener
 */
class SegmentFiltersChoicesGenerateSubscriber implements EventSubscriberInterface
{
    use OperatorListTrait;

    /**
     * @var CustomObjectRepository
     */
    private $customObjectRepository;

    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * SegmentFiltersChoicesGenerateSubscriber constructor.
     *
     * @param CustomObjectRepository $customObjectRepository
     * @param TranslatorInterface    $translator
     */
    public function __construct(
        CustomObjectRepository $customObjectRepository,
        TranslatorInterface $translator
    )
    {

        $this->customObjectRepository = $customObjectRepository;
        $this->translator             = $translator;
    }

    /**
     * @return array
     */
    public static function getSubscribedEvents(): array
    {
        return [LeadEvents::LIST_FILTERS_CHOICES_ON_GENERATE => 'onGenerateSegmentFilters'];
    }

    /**
     * @param LeadListFiltersChoicesEvent $event
     */
    public function onGenerateSegmentFilters(LeadListFiltersChoicesEvent $event): void
    {
        $criteria     = new Criteria(Criteria::expr()->eq('isPublished', 1));

        $this->customObjectRepository->matching($criteria)->map(
            function (CustomObject $customObject) use ($event) {
                $event->addChoice(
                    'custom_object',
                    'cmo_' . $customObject->getId(),
                    [
                        'label'      => $customObject->getName() . " " . $this->translator->trans('custom.item.name.label'),
                        'properties' => ['type' => 'text'],
                        'operators'  => $this->getOperatorsForFieldType('text'),
                        'object'     => $customObject->getId(),
                    ]
                );
                foreach ($customObject->getFields()->getIterator() as $customField) {
                    $event->addChoice(
                        'custom_object',
                        'cmf_' . $customField->getId(),
                        [
                            'label'      => $customField->getCustomObject()->getName() . " : " . $customField->getLabel(),
                            'properties' => ['type' => $customField->getType()],
                            'operators'  => $customField->getType(), //@todo wip
                            'object'     => $customField->getId(),
                        ]
                    );
                };
            }
        );
    }
}