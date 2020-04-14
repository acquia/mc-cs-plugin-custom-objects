<?php

declare(strict_types=1);

/*
 * @copyright   2018 Mautic, Inc. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.com
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\CustomObjectsBundle\EventListener;

use Mautic\CoreBundle\CoreEvents;
use Mautic\CoreBundle\Event\CustomContentEvent;
use MauticPlugin\CustomObjectsBundle\Entity\CustomItem;
use MauticPlugin\CustomObjectsBundle\Entity\CustomObject;
use MauticPlugin\CustomObjectsBundle\Model\CustomObjectModel;
use MauticPlugin\CustomObjectsBundle\Provider\CustomItemRouteProvider;
use MauticPlugin\CustomObjectsBundle\Repository\CustomItemRepository;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Translation\TranslatorInterface;

class CustomItemTabSubscriber implements EventSubscriberInterface
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
     * @var CustomItemRouteProvider
     */
    private $customItemRouteProvider;

    /**
     * @var CustomObject[]
     */
    private $customObjects = [];

    public function __construct(
        CustomObjectModel $customObjectModel,
        CustomItemRepository $customItemRepository,
        TranslatorInterface $translator,
        CustomItemRouteProvider $customItemRouteProvider
    ) {
        $this->customObjectModel       = $customObjectModel;
        $this->customItemRepository    = $customItemRepository;
        $this->translator              = $translator;
        $this->customItemRouteProvider = $customItemRouteProvider;
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
        if ($event->checkContext('CustomObjectsBundle:CustomItem:detail.html.php', 'tabs')) {
            $vars    = $event->getVars();
            $objects = array_filter(
                $this->getCustomObjects(),
                function ($object) {
                    return $object->getType() === CustomObject::TYPE_MASTER;
                }
            );

            /** @var CustomItem $item */
            $item = $vars['item'];

            /** @var CustomObject $object */
            foreach ($objects as $object) {
                if ($object->getId() === $item->getCustomObject()->getId()) {
                    continue;
                }

                $data = [
                    'title' => $object->getNamePlural(),
                    'count' => $this->customItemRepository->countItemsLinkedToAnotherItem($object, $item),
                    'tabId' => "custom-object-{$object->getId()}",
                ];

                $event->addTemplate('CustomObjectsBundle:SubscribedEvents/Tab:link.html.php', $data);
            }
        }

        if ($event->checkContext('CustomObjectsBundle:CustomItem:detail.html.php', 'tabs.content')) {
            $vars    = $event->getVars();
            $objects = $this->getCustomObjects();

            /** @var CustomItem $item */
            $item = $vars['item'];

            /** @var CustomObject $object */
            foreach ($objects as $object) {
                $data = [
                    'customObjectId'    => $object->getId(),
                    'currentEntityId'   => $item->getId(),
                    'currentEntityType' => 'customItem',
                    'tabId'             => "custom-object-{$object->getId()}",
                    'page'              => 1,
                    'search'            => '',
                    'placeholder'       => $this->translator->trans('custom.item.link.search.placeholder', ['%object%' => $object->getNameSingular()]),
                    'lookupRoute'       => $this->customItemRouteProvider->buildLookupRoute((int) $object->getId(), 'customItem', (int) $item->getId()),
                    'newRoute'          => $this->customItemRouteProvider->buildNewRoute((int) $object->getId()),
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
