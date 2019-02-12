<?php
declare(strict_types=1);

/*
 * @copyright   2019 Mautic Contributors. All rights reserved
 * @author      Mautic Inc.
 *
 * @link        http://mautic.com
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\CustomObjectsBundle\EventListener;

use Doctrine\ORM\EntityManager;
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\LeadBundle\Event\SegmentDictionaryGenerationEvent;
use Mautic\LeadBundle\LeadEvents;
use MauticPlugin\CustomObjectsBundle\CustomObjectsBundle;
use MauticPlugin\CustomObjectsBundle\Exception\InvalidArgumentException;
use MauticPlugin\CustomObjectsBundle\Segment\Query\Filter\CustomFieldFilterQueryBuilder;
use MauticPlugin\CustomObjectsBundle\Segment\Query\Filter\CustomItemFilterQueryBuilder;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * SegmentFiltersDictionarySubscriber
 *
 * @package MauticPlugin\CustomObjectsBundle\EventListener
 */
class SegmentFiltersDictionarySubscriber implements EventSubscriberInterface
{

    /**
     * @var EntityManager
     */
    private $entityManager;
    /**
     * @var CoreParametersHelper
     */
    private $coreParametersHelper;

    /**
     * @param EntityManager        $entityManager
     * @param CoreParametersHelper $coreParametersHelper
     */
    public function __construct(EntityManager $entityManager, CoreParametersHelper $coreParametersHelper)
    {
        $this->entityManager        = $entityManager;
        $this->coreParametersHelper = $coreParametersHelper;
    }

    /**
     * @return array
     */
    public static function getSubscribedEvents(): array
    {
        return [LeadEvents::SEGMENT_DICTIONARY_ON_GENERATE => 'onGenerateSegmentDictionary'];
    }

    /**
     * @param SegmentDictionaryGenerationEvent $event
     *
     * @throws InvalidArgumentException
     */
    public function onGenerateSegmentDictionary(SegmentDictionaryGenerationEvent $event): void
    {
        if (!$this->coreParametersHelper->getParameter(CustomObjectsBundle::CONFIG_PARAM_ENABLED)) {
            return;
        }

        $queryBuilder = $this->entityManager->getConnection()->createQueryBuilder();
        $queryBuilder
            ->select('f.id, f.label, f.type, o.id as custom_object_id')
            ->from(MAUTIC_TABLE_PREFIX . "custom_field", 'f')
            ->innerJoin('f', MAUTIC_TABLE_PREFIX . "custom_object", 'o', 'f.custom_object_id = o.id and o.is_published = 1');

        $registeredObjects = [];

        foreach ($queryBuilder->execute()->fetchAll() as $field) {
            if (!in_array($COId = $field['custom_object_id'], $registeredObjects)) {
                $event->addTranslation('cmo_' . $COId, [
                    'type'  => CustomItemFilterQueryBuilder::getServiceId(),
                    'field' => $COId,
                    'foreign_table' => 'custom_objects'
                ]);
                $registeredObjects[] = $COId;
            }
            $event->addTranslation('cmf_' . $field['id'], $this->createTranslation($field));
        }
    }

    /**
     * @param array $fieldAttributes
     *
     * @return array
     */
    private function createTranslation(array $fieldAttributes): array
    {
        $segmentValueType = 'custom_field_value_' . $fieldAttributes['type'];

        $translation = [
            'type'  => CustomFieldFilterQueryBuilder::getServiceId(),
            'table' => $segmentValueType,
            'field' => $fieldAttributes['id'],
            'foreign_table' => 'custom_objects'
        ];

        return $translation;
    }
}