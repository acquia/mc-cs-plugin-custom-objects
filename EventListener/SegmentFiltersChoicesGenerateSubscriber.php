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
use Mautic\LeadBundle\Event\LeadListFiltersChoicesEvent;
use Mautic\LeadBundle\LeadEvents;
use MauticPlugin\CustomObjectsBundle\Entity\CustomObject;
use MauticPlugin\CustomObjectsBundle\Repository\CustomFieldRepository;
use MauticPlugin\CustomObjectsBundle\Repository\CustomItemRepository;
use MauticPlugin\CustomObjectsBundle\Repository\CustomObjectRepository;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class SegmentFiltersChoicesGenerateSubscriber implements EventSubscriberInterface
{
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
     * SegmentFiltersChoicesGenerateSubscriber constructor.
     *
     * @param CustomObjectRepository $customObjectRepository
     * @param CustomFieldRepository  $customFieldRepository
     * @param CustomItemRepository   $customItemRepository
     */
    public function __construct(
        CustomObjectRepository $customObjectRepository,
        CustomFieldRepository $customFieldRepository,
        CustomItemRepository $customItemRepository
    )
    {

        $this->customObjectRepository = $customObjectRepository;
        $this->customFieldRepository  = $customFieldRepository;
        $this->customItemRepository   = $customItemRepository;
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
        $criteria = new Criteria(Criteria::expr()->eq('isPublished', 1));

        $customObjects = $this->customObjectRepository->matching($criteria)->forAll(
            function (int $index, CustomObject $customObject) {
                $customFields = $customObject->getFields()->forAll(
                    function (int $i, $customField) {
                        var_dump($customField);
                        $choiceItem = [
                            'lead' => [
                                'date_added' => [
                                    'label'      => $this->translator->trans('mautic.core.date.added'),
                                    'properties' => ['type' => 'date'],
                                    'operators'  => $this->getOperatorsForFieldType('default'),
                                    'object'     => 'lead',
                                ],
                            ]
                        ];
                    }
                );
            }
        );
        die();
    }
}