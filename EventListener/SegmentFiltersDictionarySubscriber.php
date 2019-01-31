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
use Doctrine\ORM\EntityManager;
use Mautic\LeadBundle\Entity\OperatorListTrait;
use Mautic\LeadBundle\Event\LeadListFiltersChoicesEvent;
use Mautic\LeadBundle\Event\SegmentDictionaryGenerationEvent;
use Mautic\LeadBundle\LeadEvents;
use MauticPlugin\CustomObjectsBundle\Entity\CustomObject;
use MauticPlugin\CustomObjectsBundle\Exception\InvalidArgumentException;
use MauticPlugin\CustomObjectsBundle\Repository\CustomFieldRepository;
use MauticPlugin\CustomObjectsBundle\Repository\CustomItemRepository;
use MauticPlugin\CustomObjectsBundle\Repository\CustomObjectRepository;
use MauticPlugin\CustomObjectsBundle\Segment\Query\Filter\CustomFieldFilterQueryBuilder;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Translation\TranslatorInterface;

class SegmentFiltersDictionarySubscriber implements EventSubscriberInterface
{

    /**
     * @var EntityManager
     */
    private $entityManager;

    public function __construct(
        EntityManager $entityManager
    )
    {
        $this->entityManager = $entityManager;
    }

    /**
     * @return array
     */
    public static function getSubscribedEvents(): array
    {
        return [LeadEvents::SEGMENT_DICTIONARY_ON_GENERATE => 'onGenerateSegmentDictionary'];
    }

    public function onGenerateSegmentDictionary(SegmentDictionaryGenerationEvent $event)
    {
        $queryBuilder = $this->entityManager->getConnection()->createQueryBuilder();
        $queryBuilder
            ->select('f.id, f.label, f.type')
            ->from(MAUTIC_TABLE_PREFIX . "custom_field", 'f')
            ->innerJoin('f', MAUTIC_TABLE_PREFIX . "custom_object", 'o', 'f.custom_object_id = o.id and o.is_published = 1');

        foreach ($queryBuilder->execute()->fetchAll() as $field) {
            $event->addTranslation('cmf_' . $field['id'], $this->createTranslation($field));
        }
    }

    private function createTranslation(array $fieldAttributes)
    {

        switch ($type = $fieldAttributes['type']) {
            case 'int':
                $segmentValueType = 'number';
                break;
            case 'text':
                $segmentValueType = 'text';
                break;
            case 'datetime':
                $segmentValueType = 'datetime';
                break;
            default:
                throw new InvalidArgumentException('Given custom field type does not exist: ' . $type);
        }

        $segmentValueType = 'custom_field_value_' . $type;

        $translation = [
            'type'  => CustomFieldFilterQueryBuilder::getServiceId(),
            'table' => $segmentValueType,
            'field' => $fieldAttributes['id'],
        ];

        return $translation;
    }
}