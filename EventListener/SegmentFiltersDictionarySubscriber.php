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

use Doctrine\Bundle\DoctrineBundle\Registry;
use Mautic\LeadBundle\Event\SegmentDictionaryGenerationEvent;
use Mautic\LeadBundle\LeadEvents;
use Mautic\LeadBundle\Segment\Query\QueryBuilder;
use MauticPlugin\CustomObjectsBundle\Segment\Query\Filter\CustomFieldFilterQueryBuilder;
use MauticPlugin\CustomObjectsBundle\Segment\Query\Filter\CustomItemFilterQueryBuilder;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use MauticPlugin\CustomObjectsBundle\Provider\ConfigProvider;

class SegmentFiltersDictionarySubscriber implements EventSubscriberInterface
{
    /**
     * @var Registry
     */
    private $doctrineRegistry;

    /**
     * @var ConfigProvider
     */
    private $configProvider;

    /**
     * @param Registry       $registry
     * @param ConfigProvider $configProvider
     */
    public function __construct(Registry $registry, ConfigProvider $configProvider)
    {
        $this->doctrineRegistry = $registry;
        $this->configProvider   = $configProvider;
    }

    /**
     * @return mixed[]
     */
    public static function getSubscribedEvents(): array
    {
        return [
            LeadEvents::SEGMENT_DICTIONARY_ON_GENERATE => 'onGenerateSegmentDictionary',
        ];
    }

    /**
     * @param SegmentDictionaryGenerationEvent $event
     *
     * @throws \Doctrine\DBAL\DBALException
     */
    public function onGenerateSegmentDictionary(SegmentDictionaryGenerationEvent $event): void
    {
        if (!$this->configProvider->pluginIsEnabled()) {
            return;
        }

        // This avoids exceptions if no connection is available as in cache:clear
        if (!$this->doctrineRegistry->getConnection()->isConnected()) {
            return;
        }

        /** @var QueryBuilder $queryBuilder */
        $queryBuilder = $this->doctrineRegistry->getConnection()->createQueryBuilder();
        $queryBuilder
            ->select('f.id, f.label, f.type, o.id as custom_object_id')
            ->from(MAUTIC_TABLE_PREFIX.'custom_field', 'f')
            ->innerJoin('f', MAUTIC_TABLE_PREFIX.'custom_object', 'o', 'f.custom_object_id = o.id');

        $registeredObjects = [];

        $fields = $queryBuilder->execute()->fetchAll();

        foreach ($fields as $field) {
            $COId = $field['custom_object_id'];
            if (!in_array($COId, $registeredObjects, true)) {
                $event->addTranslation('cmo_'.$COId, [
                    'type'          => CustomItemFilterQueryBuilder::getServiceId(),
                    'field'         => $COId,
                    'foreign_table' => 'custom_objects',
                ]);
                $registeredObjects[] = $COId;
            }
            $event->addTranslation('cmf_'.$field['id'], $this->createTranslation($field));
        }
    }

    /**
     * @param mixed[] $fieldAttributes
     *
     * @return mixed[]
     */
    private function createTranslation(array $fieldAttributes): array
    {
        $segmentValueType = 'custom_field_value_'.$fieldAttributes['type'];

        return [
            'type'          => CustomFieldFilterQueryBuilder::getServiceId(),
            'table'         => $segmentValueType,
            'field'         => $fieldAttributes['id'],
            'foreign_table' => 'custom_objects',
        ];
    }
}
