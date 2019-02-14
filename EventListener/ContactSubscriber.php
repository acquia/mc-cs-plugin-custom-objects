<?php

/*
 * @copyright   2019 Mautic, Inc. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.com
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\CustomObjectsBundle\EventListener;

use Mautic\CoreBundle\EventListener\CommonSubscriber;
use Mautic\LeadBundle\LeadEvents;
use Mautic\LeadBundle\Event\LeadTimelineEvent;
use MauticPlugin\CustomObjectsBundle\Provider\CustomItemRouteProvider;
use Symfony\Component\Translation\TranslatorInterface;
use MauticPlugin\CustomObjectsBundle\Model\CustomItemModel;
use MauticPlugin\CustomObjectsBundle\Exception\NotFoundException;
use Doctrine\ORM\EntityManager;

class ContactSubscriber extends CommonSubscriber
{
    /**
     * @var EntityManager
     */
    private $entityManager;

    /**
     * @var CustomItemRouteProvider
     */
    private $routeProvider;

    /**
     * @var CustomItemModel
     */
    private $customItemModel;

    /**
     * @param EntityManager $entityManager
     * @param TranslatorInterface $translator
     * @param CustomItemRouteProvider $routeProvider
     * @param CustomItemModel $customItemModel
     */
    public function __construct(
        EntityManager $entityManager,
        TranslatorInterface $translator,
        CustomItemRouteProvider $routeProvider,
        CustomItemModel $customItemModel
    )
    {
        $this->entityManager   = $entityManager;
        $this->translator      = $translator;
        $this->routeProvider   = $routeProvider;
        $this->customItemModel = $customItemModel;
    }

    /**
     * @return array
     */
    public static function getSubscribedEvents(): array
    {
        return [
            LeadEvents::TIMELINE_ON_GENERATE => 'onTimelineGenerate',
        ];
    }

    /**
     * Compile events for the lead timeline.
     *
     * @param LeadTimelineEvent $event
     */
    public function onTimelineGenerate(LeadTimelineEvent $event)
    {
        $eventTypes = [
            'customitem.linked'   => 'custom.item.event.linked',
            'customitem.unlinked' => 'custom.item.event.unlinked',
        ];

        $filters = $event->getEventFilters();

        foreach ($eventTypes as $type => $label) {
            $name = $this->translator->trans($label);
            $event->addEventType($type, $name);

            if (!$event->isApplicable($type) || !empty($filters['search'])) {
                continue;
            }

            if ('customitem.linked' === $type) {
                $this->addLinkTimelineEntry($event, $type, $name, 'link');
            } elseif ('customitem.unlinked' === $type) {
                $this->addLinkTimelineEntry($event, $type, $name, 'unlink');
            }
        }
    }

    /**
     * @param LeadTimelineEvent $event
     * @param string            $action
     * 
     * @return array
     */
    private function getEvents(LeadTimelineEvent $event, string $action): array
    {
        $eventLogRepo = $this->entityManager->getRepository('MauticLeadBundle:LeadEventLog');

        return $eventLogRepo->getEvents(
            $event->getLead(),
            'CustomObject',
            'CustomItem',
            [$action],
            $event->getQueryOptions()
        );
    }

    /**
     * @param LeadTimelineEvent $event
     * @param string            $eventTypeKey
     * @param string            $eventTypeName
     * @param string            $action
     */
    private function addLinkTimelineEntry(LeadTimelineEvent $event, string $eventTypeKey, string $eventTypeName, string $action)
    {
        $links = $this->getEvents($event, $action);

        $event->addToCounter($eventTypeKey, $links);

        if (!$event->isEngagementCount()) {
            foreach ($links['results'] as $link) {
                try {
                    $customItem = $this->customItemModel->fetchEntity($link['object_id']);
                    $eventLabel = [
                        'label' => $this->translator->trans("custom.item.{$action}.event", ['%customItemName%' => $customItem->getName()]),
                        'href'  => $this->routeProvider->buildViewRoute($customItem->getCustomObject()->getId(), $customItem->getId()),
                    ];
                } catch (NotFoundException $e) {
                    $eventLabel = $this->translator->trans("custom.item.{$action}.event.not.found", ['%customItemId%' => $link['object_id']]);
                }
                $event->addEvent([
                        'event'           => $eventTypeKey,
                        'eventId'         => $eventTypeKey.$link['id'],
                        'eventType'       => $eventTypeName,
                        'eventLabel'      => $eventLabel,
                        'timestamp'       => $link['date_added'],
                        'icon'            => "fa-{$action}",
                        'extra'           => $link,
                        'contactId'       => $link['lead_id'],
                        'contentTemplate' => 'CustomObjectsBundle:SubscribedEvents\Timeline:link.html.php',
                ]);
            }
        }
    }
}
