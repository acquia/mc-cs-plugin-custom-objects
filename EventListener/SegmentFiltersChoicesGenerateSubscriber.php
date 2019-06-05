<?php

declare(strict_types=1);

/*
 * @copyright   2019 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc
 *
 * @link        https://mautic.com
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
use MauticPlugin\CustomObjectsBundle\Repository\CustomObjectRepository;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Translation\TranslatorInterface;
use MauticPlugin\CustomObjectsBundle\Provider\ConfigProvider;

/**
 * SegmentFiltersChoicesGenerateSubscriber.
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
     * @var ConfigProvider
     */
    private $configProvider;

    /**
     * @param CustomObjectRepository $customObjectRepository
     * @param TranslatorInterface    $translator
     * @param ConfigProvider         $configProvider
     */
    public function __construct(
        CustomObjectRepository $customObjectRepository,
        TranslatorInterface $translator,
        ConfigProvider $configProvider
    ) {
        $this->customObjectRepository = $customObjectRepository;
        $this->translator             = $translator;
        $this->configProvider         = $configProvider;
    }

    /**
     * @return mixed[]
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
        if (!$this->configProvider->pluginIsEnabled()) {
            return;
        }

        $criteria = new Criteria(Criteria::expr()->eq('isPublished', 1));

        $this->customObjectRepository->matching($criteria)->map(
            function (CustomObject $customObject) use ($event): void {
                $event->addChoice(
                    'custom_object',
                    'cmo_'.$customObject->getId(),
                    [
                        'label'      => $customObject->getName().' '.$this->translator->trans('custom.item.name.label'),
                        'properties' => ['type' => 'text'],
                        'operators'  => $this->getOperatorsForFieldType('text'),
                        'object'     => $customObject->getId(),
                    ]
                );

                /** @var CustomField $customField */
                foreach ($customObject->getCustomFields()->getIterator() as $customField) {
                    $availableOperator = $this->getOperatorsForFieldType($customField->getType());
                    $allowedOperators = $customField->getTypeObject()->getOperators();
                    $operators = array_intersect_key($availableOperator, $allowedOperators);

                    $properties = ['type' => $customField->getType()];
                    $choices    = $customField->getChoices();

                    if (!empty($choices)) {
                        $properties['list'] = $choices;
                    }

                    $event->addChoice(
                        'custom_object',
                        'cmf_'.$customField->getId(),
                        [
                            'label'      => $customField->getCustomObject()->getName().' : '.$customField->getLabel(),
                            'properties' => $properties,
                            'operators'  => $operators,
                            'object'     => $customField->getId(),
                        ]
                    );
                }
            }
        );
    }
}
