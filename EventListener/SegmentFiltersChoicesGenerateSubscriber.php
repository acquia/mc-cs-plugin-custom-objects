<?php

declare(strict_types=1);

namespace MauticPlugin\CustomObjectsBundle\EventListener;

use Doctrine\Common\Collections\Criteria;
use Mautic\LeadBundle\Entity\OperatorListTrait;
use Mautic\LeadBundle\Event\LeadListFiltersChoicesEvent;
use Mautic\LeadBundle\LeadEvents;
use MauticPlugin\CustomObjectsBundle\CustomFieldType\HiddenType;
use MauticPlugin\CustomObjectsBundle\Entity\CustomField;
use MauticPlugin\CustomObjectsBundle\Entity\CustomObject;
use MauticPlugin\CustomObjectsBundle\Provider\ConfigProvider;
use MauticPlugin\CustomObjectsBundle\Provider\CustomFieldTypeProvider;
use MauticPlugin\CustomObjectsBundle\Repository\CustomObjectRepository;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

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
     * @var CustomFieldTypeProvider
     */
    private $fieldTypeProvider;

    public function __construct(
        CustomObjectRepository $customObjectRepository,
        TranslatorInterface $translator,
        ConfigProvider $configProvider,
        CustomFieldTypeProvider $fieldTypeProvider
    ) {
        $this->customObjectRepository = $customObjectRepository;
        $this->translator             = $translator;
        $this->configProvider         = $configProvider;
        $this->fieldTypeProvider      = $fieldTypeProvider;
    }

    /**
     * @return mixed[]
     */
    public static function getSubscribedEvents(): array
    {
        return [LeadEvents::LIST_FILTERS_CHOICES_ON_GENERATE => 'onGenerateSegmentFilters'];
    }

    public function onGenerateSegmentFilters(LeadListFiltersChoicesEvent $event): void
    {
        if (!$this->configProvider->pluginIsEnabled()) {
            return;
        }

        $fieldTypes = $this->fieldTypeProvider->getKeyTypeMapping();

        $criteria = new Criteria(Criteria::expr()->eq('isPublished', 1));

        $this->customObjectRepository->matching($criteria)->map(
            function (CustomObject $customObject) use ($event, $fieldTypes): void {
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
                    if ($customField->getType() === $fieldTypes[HiddenType::NAME]) { // We don't want to show hidden types in filter list
                        continue;
                    }

                    $allowedOperators = $customField->getTypeObject()->getOperators();
                    $availableOperators = array_flip($this->getOperatorsForFieldType($customField->getType()));
                    $operators = array_intersect_key($availableOperators, $allowedOperators);
                    $operators = array_flip($operators);

                    $event->addChoice(
                        'custom_object',
                        'cmf_'.$customField->getId(),
                        [
                            'label'      => $customField->getCustomObject()->getName().' : '.$customField->getLabel(),
                            'properties' => $this->getFieldProperties($customField),
                            'operators'  => $operators,
                            'object'     => $customField->getId(),
                        ]
                    );
                }
            }
        );
    }

    /**
     * @return mixed[]
     */
    private function getFieldProperties(CustomField $customField): array
    {
        $type = $customField->isChoiceType() ? 'select' : $customField->getType();

        $properties = ['type' => $type];

        if ($customField->isChoiceType()) {
            $properties['list'] = $customField->getChoices();
        }

        return $properties;
    }
}
