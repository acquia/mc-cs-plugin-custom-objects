<?php

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
use Mautic\CoreBundle\EventListener\CommonSubscriber;
use Mautic\CoreBundle\Event\CustomContentEvent;
use MauticPlugin\CustomObjectsBundle\Model\CustomObjectModel;
use MauticPlugin\CustomObjectsBundle\Entity\CustomObject;
use MauticPlugin\CustomObjectsBundle\Model\CustomItemModel;
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use MauticPlugin\CustomObjectsBundle\CustomObjectsBundle;

class TabSubscriber extends CommonSubscriber
{
    /**
     * @var CustomObjectModel
     */
    private $customObjectModel;

    /**
     * @var CustomItemModel
     */
    private $customItemModel;

    /**
     * @var CoreParametersHelper
     */
    private $coreParametersHelper;

    /**
     * @var CustomObject[]
     */
    private $customObjects = [];

    /**
     * @param CustomObjectModel    $customObjectModel
     * @param CustomItemModel      $customItemModel
     * @param CoreParametersHelper $coreParametersHelper
     */
    public function __construct(
        CustomObjectModel $customObjectModel,
        CustomItemModel $customItemModel,
        CoreParametersHelper $coreParametersHelper
    )
    {
        $this->customObjectModel    = $customObjectModel;
        $this->customItemModel      = $customItemModel;
        $this->coreParametersHelper = $coreParametersHelper;
    }

    /**
     * @return array
     */
    public static function getSubscribedEvents(): array
    {
        return [
            CoreEvents::VIEW_INJECT_CUSTOM_CONTENT => ['injectTabs', 0],
        ];
    }

    /**
     * @param CustomContentEvent $event
     */
    public function injectTabs(CustomContentEvent $event): void
    {
        $isEnabled = $this->coreParametersHelper->getParameter(CustomObjectsBundle::CONFIG_PARAM_ENABLED);

        if (!$isEnabled) {
            return;
        }

        if ($event->checkContext('MauticLeadBundle:Lead:lead.html.php', 'tabs')) {
            $vars    = $event->getVars();
            $objects = $this->getCustomObjects();
            $contact = $vars['lead'];

            foreach ($objects as $object) {
                $data = [
                    'customObject' => $object,
                    'count'        => $this->customItemModel->countItemsLinkedToContact($object, $contact),
                ];
    
                $event->addTemplate('CustomObjectsBundle:SubscribedEvents/Tab:link.html.php', $data);
            }
        }

        if ($event->checkContext('MauticLeadBundle:Lead:lead.html.php', 'tabs.content')) {
            $vars    = $event->getVars();
            $contact = $vars['lead'];
            $objects = $this->getCustomObjects();

            foreach ($objects as $object) {
                $data = [
                    'customObject' => $object,
                    'page'  => 1,
                    'search' => '',
                ];
    
                $event->addTemplate('CustomObjectsBundle:SubscribedEvents/Tab:content.html.php', $data);
            }
        }
    }

    /**
     * Apart from fetchin the custom object list this method also caches them to the memory and
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
