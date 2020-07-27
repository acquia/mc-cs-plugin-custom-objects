<?php

declare(strict_types=1);

/*
 * @copyright   2019 Mautic, Inc. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.com
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\CustomObjectsBundle\EventListener;

use Mautic\CoreBundle\CoreEvents;
use Mautic\CoreBundle\Event\CustomContentEvent;
use Mautic\LeadBundle\Entity\Lead;
use MauticPlugin\CustomObjectsBundle\Entity\CustomObject;
use MauticPlugin\CustomObjectsBundle\Model\CustomObjectModel;
use MauticPlugin\CustomObjectsBundle\Provider\ConfigProvider;
use MauticPlugin\CustomObjectsBundle\Provider\CustomItemRouteProvider;
use MauticPlugin\CustomObjectsBundle\Provider\SessionProviderFactory;
use MauticPlugin\CustomObjectsBundle\Repository\CustomItemRepository;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Translation\TranslatorInterface;

class ContactTabSubscriber implements EventSubscriberInterface
{
    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * @var CustomObjectModel
     */
    private $customObjectModel;

    /**
     * @var CustomItemRepository
     */
    private $customItemRepository;

    /**
     * @var ConfigProvider
     */
    private $configProvider;

    /**
     * @var CustomItemRouteProvider
     */
    private $customItemRouteProvider;

    /**
     * @var CustomObject[]
     */
    private $customObjects = [];

    /**
     * @var SessionProviderFactory
     */
    private $sessionProviderFactory;

    public function __construct(
        CustomObjectModel $customObjectModel,
        CustomItemRepository $customItemRepository,
        ConfigProvider $configProvider,
        TranslatorInterface $translator,
        CustomItemRouteProvider $customItemRouteProvider,
        SessionProviderFactory $sessionProviderFactory
    ) {
        $this->customObjectModel       = $customObjectModel;
        $this->customItemRepository    = $customItemRepository;
        $this->configProvider          = $configProvider;
        $this->translator              = $translator;
        $this->customItemRouteProvider = $customItemRouteProvider;
        $this->sessionProviderFactory  = $sessionProviderFactory;
    }

    /**
     * @return mixed[]
     */
    public static function getSubscribedEvents(): array
    {
        return [
            CoreEvents::VIEW_INJECT_CUSTOM_CONTENT => ['injectTabs', 0],
        ];
    }

    public function injectTabs(CustomContentEvent $event): void
    {
        if (!$this->configProvider->pluginIsEnabled()) {
            return;
        }

        if ($event->checkContext('MauticLeadBundle:Lead:lead.html.php', 'tabs')) {
            $vars    = $event->getVars();
            $objects = $this->customObjectModel->getMasterCustomObjects();

            /** @var Lead $contact */
            $contact = $vars['lead'];

            /** @var CustomObject $object */
            foreach ($objects as $object) {
                $data = [
                    'title' => $object->getNamePlural(),
                    'count' => $this->customItemRepository->countItemsLinkedToContact($object, $contact),
                    'tabId' => "custom-object-{$object->getId()}",
                ];

                $event->addTemplate('CustomObjectsBundle:SubscribedEvents/Tab:link.html.php', $data);
            }
        }

        if ($event->checkContext('MauticLeadBundle:Lead:lead.html.php', 'tabs.content')) {
            $vars    = $event->getVars();
            $objects = $this->getCustomObjects();

            /** @var Lead $contact */
            $contact    = $vars['lead'];
            $entityType = 'contact';

            /** @var CustomObject $object */
            foreach ($objects as $object) {
                $objectId  = (int) $object->getId();
                $contactId = (int) $contact->getId();
                $data      = [
                    'customObjectId'       => $objectId,
                    'currentEntityId'      => $contactId,
                    'currentEntityType'    => $entityType,
                    'tabId'                => "custom-object-{$objectId}",
                    'searchId'             => "list-search-{$objectId}",
                    'searchValue'          => $this->sessionProviderFactory->createItemProvider($objectId, $entityType, $contactId)->getFilter(),
                    'placeholder'          => $this->translator->trans('custom.item.link.search.placeholder.contact', ['%object%' => $object->getNameSingular()]),
                    'searchRoute'          => $this->customItemRouteProvider->buildListRoute($objectId, 1, $entityType, $contactId),
                    'lookupRoute'          => $this->customItemRouteProvider->buildLookupRoute($objectId, $entityType, $contactId),
                    'newRoute'             => $this->customItemRouteProvider->buildNewRouteWithRedirectToContact($objectId, $contactId),
                    'relationshipObjectId' => $object->getRelationshipObject() ? $object->getRelationshipObject()->getId() : null,
                ];

                $event->addTemplate('CustomObjectsBundle:SubscribedEvents/Tab:content.html.php', $data);
            }
        }
    }

    /**
     * Apart from fetching the custom object list this method also caches them to the memory and
     * use the list from memory if called multiple times.
     *
     * @return CustomObject[]
     */
    private function getCustomObjects(): array
    {
        if (!$this->customObjects) {
            $this->customObjects = $this->customObjectModel->fetchAllPublishedEntities();
        }

        return $this->customObjects;
    }
}
