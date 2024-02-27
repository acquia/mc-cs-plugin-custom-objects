<?php

declare(strict_types=1);

namespace MauticPlugin\CustomObjectsBundle\EventListener;

use Doctrine\DBAL\Connection;
use Doctrine\Persistence\ManagerRegistry;
use Mautic\LeadBundle\Event\SegmentDictionaryGenerationEvent;
use Mautic\LeadBundle\LeadEvents;
use Mautic\LeadBundle\Segment\Query\QueryBuilder;
use MauticPlugin\CustomObjectsBundle\Provider\ConfigProvider;
use MauticPlugin\CustomObjectsBundle\Repository\DbalQueryTrait;
use MauticPlugin\CustomObjectsBundle\Segment\Query\Filter\CustomFieldFilterQueryBuilder;
use MauticPlugin\CustomObjectsBundle\Segment\Query\Filter\CustomItemNameFilterQueryBuilder;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class SegmentFiltersDictionarySubscriber implements EventSubscriberInterface
{
    use DbalQueryTrait;

    private ManagerRegistry $doctrineRegistry;

    private ConfigProvider $configProvider;

    public function __construct(ManagerRegistry $registry, ConfigProvider $configProvider)
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
     * @throws \Doctrine\DBAL\DBALException
     */
    public function onGenerateSegmentDictionary(SegmentDictionaryGenerationEvent $event): void
    {
        if (!$this->configProvider->pluginIsEnabled()) {
            return;
        }

        /** @var Connection $connection */
        $connection = $this->doctrineRegistry->getConnection();

        /** @var QueryBuilder $queryBuilder */
        $queryBuilder = $connection->createQueryBuilder();
        $queryBuilder
            ->select('f.id, f.label, f.type, o.id as custom_object_id')
            ->from(MAUTIC_TABLE_PREFIX.'custom_object', 'o')
            ->leftJoin('o', MAUTIC_TABLE_PREFIX.'custom_field', 'f', 'f.custom_object_id = o.id');

        $registeredObjects = [];
        $fields            = $this->executeSelect($queryBuilder)->fetchAll();

        foreach ($fields as $field) {
            $COId = $field['custom_object_id'];
            if (!in_array($COId, $registeredObjects, true)) {
                $event->addTranslation('cmo_'.$COId, [
                    'type'          => CustomItemNameFilterQueryBuilder::getServiceId(),
                    'field'         => $COId,
                    'foreign_table' => 'custom_objects',
                ]);
                $registeredObjects[] = $COId;
            }
            if (!$event->hasTranslation('cmf_'.$field['id']) && !empty($field['id'])) {
                $event->addTranslation('cmf_'.$field['id'], $this->createTranslation($field));
            }
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
