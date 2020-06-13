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

use MauticPlugin\CustomObjectsBundle\Entity\CustomObject;
use MauticPlugin\CustomObjectsBundle\Event\CustomObjectEvent;
use MauticPlugin\CustomObjectsBundle\EventListener\CustomObjectPostSaveSubscriber;
use MauticPlugin\CustomObjectsBundle\Model\CustomObjectModel;
use PHPUnit\Framework\Assert;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class CustomObjectPostSaveSubscriberTest extends KernelTestCase
{
    public function testPostSaveRelationshipObject(): void
    {
        $customObject = new CustomObject();
        $customObject->setType(CustomObject::TYPE_RELATIONSHIP);
        $masterObject = new CustomObject();
        $masterObject->setType(CustomObject::TYPE_MASTER);
        $customObject->setMasterObject($masterObject);

        $customObjectModel = new class($masterObject) extends CustomObjectModel {
            private $masterObject;

            public function __construct(CustomObject $masterObject)
            {
                $this->masterObject = $masterObject;
            }

            public function saveEntity($entity, $unlock = true)
            {
                Assert::assertSame($this->masterObject, $entity);
            }
        };

        $event = new CustomObjectEvent($customObject);
        $subscriber = new CustomObjectPostSaveSubscriber($customObjectModel);

        $subscriber->postSave($event);
    }

    public function testPostSaveMasterObject(): void
    {
        $customObjectModel = new class() extends CustomObjectModel {
            public function __construct()
            {
                // noop
            }

            public function saveEntity($entity, $unlock = true)
            {
                throw new \Exception('Should not have been called.');
            }
        };

        $customObject = new CustomObject();
        $customObject->setType(CustomObject::TYPE_MASTER);

        $event = new CustomObjectEvent($customObject);
        $subscriber = new CustomObjectPostSaveSubscriber($customObjectModel);

        $subscriber->postSave($event);

        // Asserting that the exception is not thrown
        $this->addToAssertionCount(1);
    }
}