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
use Mautic\LeadBundle\Event\SegmentDictionaryGenerationEvent;
use Mautic\LeadBundle\LeadEvents;
use MauticPlugin\CustomObjectsBundle\Segment\Query\Filter\CustomFieldFilterQueryBuilder;
use MauticPlugin\CustomObjectsBundle\Segment\Query\Filter\CustomItemFilterQueryBuilder;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use MauticPlugin\CustomObjectsBundle\Provider\ConfigProvider;

class SegmentFiltersDictionarySubscriber implements EventSubscriberInterface
{
    /**
     * @var EntityManager
     */
    private $entityManager;

    /**
     * @var ConfigProvider
     */
    private $configProvider;

    /**
     * @param EntityManager  $entityManager
     * @param ConfigProvider $configProvider
     */
    public function __construct(EntityManager $entityManager, ConfigProvider $configProvider)
    {
        $this->entityManager  = $entityManager;
        $this->configProvider = $configProvider;
    }

    /**
     * @return array
     */
    public static function getSubscribedEvents(): array
    {
        return [
            // @todo enable once https://github.com/mautic-inc/mautic-cloud/pull/388 is in beta
            //LeadEvents::SEGMENT_DICTIONARY_ON_GENERATE => 'onGenerateSegmentDictionary',
        ];
    }

    /**
     * @param SegmentDictionaryGenerationEvent $event
     */
    public function onGenerateSegmentDictionary(SegmentDictionaryGenerationEvent $event): void
    {
        if (!$this->configProvider->pluginIsEnabled()) {
            return;
        }

        $queryBuilder = $this->entityManager->getConnection()->createQueryBuilder();
        $queryBuilder
            ->select('f.id, f.label, f.type, o.id as custom_object_id')
            ->from(MAUTIC_TABLE_PREFIX . 'custom_field', 'f')
            ->innerJoin('f', MAUTIC_TABLE_PREFIX . 'custom_object', 'o', 'f.custom_object_id = o.id and o.is_published = 1');

        $registeredObjects = [];

        foreach ($queryBuilder->execute()->fetchAll() as $field) {
            $COId = $field['custom_object_id'];
            if (!in_array($COId, $registeredObjects, true)) {
                $event->addTranslation('cmo_' . $COId, [
                    'type'  => CustomItemFilterQueryBuilder::getServiceId(),
                    'field' => $COId,
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

        return [
            'type'  => CustomFieldFilterQueryBuilder::getServiceId(),
            'table' => $segmentValueType,
            'field' => $fieldAttributes['id'],
        ];
    }
}