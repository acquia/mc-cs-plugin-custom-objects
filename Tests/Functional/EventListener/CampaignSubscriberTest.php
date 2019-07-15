<?php

declare(strict_types=1);

/*
 * @copyright   2019 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc
 *
 * @link        https://mautic.com
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\CustomObjectsBundle\Tests\Functional\EventListener;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use MauticPlugin\CustomObjectsBundle\Tests\Functional\DataFixtures\Traits\DatabaseSchemaTrait;
use Doctrine\ORM\EntityManager;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Model\LeadModel;
use MauticPlugin\CustomObjectsBundle\Entity\CustomItem;
use MauticPlugin\CustomObjectsBundle\Model\CustomItemModel;
use MauticPlugin\CustomObjectsBundle\Tests\Functional\DataFixtures\Traits\CustomObjectsTrait;
use MauticPlugin\CustomObjectsBundle\EventListener\CampaignSubscriber;
use Mautic\CampaignBundle\Event\CampaignExecutionEvent;
use MauticPlugin\CustomObjectsBundle\Entity\CustomFieldValueText;
use MauticPlugin\CustomObjectsBundle\Model\CustomFieldModel;

class CampaignSubscriberTest extends KernelTestCase
{
    use DatabaseSchemaTrait;
    use CustomObjectsTrait;

    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * @var EntityManager
     */
    private $entityManager;

    /** 
     * @var CustomItemModel
     */
    private $customItemModel;

    /** 
     * @var CustomFieldValueModel
     */
    private $customFieldValueModel;

    /** 
     * @var CampaignSubscriber
     */
    private $campaignSubscriber;

    protected function setUp(): void
    {
        self::bootKernel();

        $this->container = static::$kernel->getContainer();

        /** @var EntityManager $entityManager */
        $entityManager       = $this->container->get('doctrine.orm.entity_manager');
        $this->entityManager = $entityManager;

        /** @var CustomItemModel $customItemModel */
        $customItemModel = $this->container->get('mautic.custom.model.item');
        $this->customItemModel = $customItemModel;

        /** @var CustomFieldValueModel $customFieldValueModel */
        $customFieldValueModel = $this->container->get('mautic.custom.model.field.value');
        $this->customFieldValueModel = $customFieldValueModel;


        /** @var CampaignSubscriber $campaignSubscriber */
        $campaignSubscriber = $this->container->get('custom_item.campaign.subscriber');
        $this->campaignSubscriber = $campaignSubscriber;

        $this->createFreshDatabaseSchema($entityManager);
    }

    public function testImportForAllFieldTypesWithValidValuesAndLinksPlusUpdateToo(): void
    {
        $fieldId      = 12;
        $fieldAlias   = 'text-test-field';
        $fieldValue   = 'abracadabra';
        $contact      = $this->createContact('john@doe.email');
        $customObject = $this->createCustomObjectWithAllFields($this->container, 'Campaign test object');
        $customItem   = new CustomItem($customObject);

        $customItem->setName('Campaign test item');

        $this->customFieldValueModel->createValuesForItem($customItem);

        $value = $customItem->findCustomFieldValueForFieldAlias($fieldAlias);

        $value->setValue($fieldValue);
        
        $this->customItemModel->save($customItem);
        $this->customItemModel->linkEntity($customItem, 'contact', (int) $contact->getId());

        $event = new CampaignExecutionEvent(
            [
                'lead'  => $contact,
                'event' => [
                    'type' => 'custom_item.1.fieldvalue',
                    'properties' => [
                        'field' => $fieldId,
                        'operator' => '=',
                        'value' => $fieldValue,
                    ],
                ],
                'eventDetails' => [],
                'systemTriggered' => [],
                'eventSettings' => [],
            ],
            null
        );

        $this->campaignSubscriber->onCampaignTriggerCondition($event);

        $this->assertTrue($event->getResult());
    }

    /**
     * @param string $email
     *
     * @return Lead
     */
    private function createContact(string $email): Lead
    {
        /** @var LeadModel $contactModel */
        $contactModel = $this->container->get('mautic.lead.model.lead');
        $contact      = new Lead();
        $contact->setEmail($email);
        $contactModel->saveEntity($contact);

        return $contact;
    }
}
