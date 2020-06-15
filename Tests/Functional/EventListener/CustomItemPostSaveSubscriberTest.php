<?php

declare(strict_types=1);

/*
 * @copyright   2020 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc
 *
 * @link        https://mautic.com
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\CustomObjectsBundle\Tests\Functional\EventListener;

use MauticPlugin\CustomObjectsBundle\CustomItemEvents;
use MauticPlugin\CustomObjectsBundle\Entity\CustomItem;
use MauticPlugin\CustomObjectsBundle\Entity\CustomObject;
use MauticPlugin\CustomObjectsBundle\Event\CustomItemEvent;
use MauticPlugin\CustomObjectsBundle\EventListener\CustomItemPostSaveSubscriber;
use MauticPlugin\CustomObjectsBundle\Model\CustomItemModel;
use MauticPlugin\CustomObjectsBundle\Provider\CustomItemRouteProvider;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

class CustomItemPostSaveSubscriberTest extends KernelTestCase
{
    public function testGetSubscribedEvents(): void
    {
        $this->assertArrayHasKey(CustomItemEvents::ON_CUSTOM_ITEM_POST_SAVE, CustomItemPostSaveSubscriber::getSubscribedEvents());
    }

    public function testPostSaveWithMasterItemDoesNotAttemptToLink(): void
    {
        $customItemModel = new class() extends CustomItemModel {
            public function __construct()
            {
                // noop
            }

            public function fetchEntity(int $id): CustomItem
            {
                throw new \Exception('This should not have been called.');
            }
        };

        $requestStack = new class() extends RequestStack {
            public function getCurrentRequest()
            {
                return new class() extends Request {
                    public function __construct()
                    {
                        parent::__construct();

                        $this->attributes->set('_route', CustomItemRouteProvider::ROUTE_LINK_FORM_SAVE);
                    }
                };
            }
        };

        $customObject = new CustomObject();
        $customObject->setType(CustomObject::TYPE_MASTER);

        $customItem = new CustomItem($customObject);
        $subscriber = new CustomItemPostSaveSubscriber($customItemModel, $requestStack);
        $event = new CustomItemEvent($customItem);

        $subscriber->postSave($event);

        // No actual assertions made, we're just ensuring an exception is not thrown.
        $this->addToAssertionCount(1);
    }

    public function testPostSaveWithRelationshipButWrongRouteItemDoesNotAttemptToLink(): void
    {
        $customItemModel = new class() extends CustomItemModel {
            public function __construct()
            {
                // noop
            }

            public function fetchEntity(int $id): CustomItem
            {
                throw new \Exception('This should not have been called.');
            }
        };

        $requestStack = new class() extends RequestStack {
            public function getCurrentRequest()
            {
                return new class() extends Request {
                    public function __construct()
                    {
                        parent::__construct();

                        $this->attributes->set('_route', 'WRONG_ROUTE');
                    }
                };
            }
        };

        $customObject = new CustomObject();
        $customObject->setType(CustomObject::TYPE_RELATIONSHIP);

        $customItem = new CustomItem($customObject);
        $subscriber = new CustomItemPostSaveSubscriber($customItemModel, $requestStack);
        $event = new CustomItemEvent($customItem);

        $subscriber->postSave($event);

        // No actual assertions made, we're just ensuring an exception is not thrown.
        $this->addToAssertionCount(1);
    }
}
