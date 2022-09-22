<?php

declare(strict_types=1);

namespace MauticPlugin\CustomObjectsBundle\Tests\Functional\EventListener;

use Mautic\CampaignBundle\Event\CampaignExecutionEvent;
use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Model\LeadModel;
use MauticPlugin\CustomObjectsBundle\Entity\CustomItem;
use MauticPlugin\CustomObjectsBundle\EventListener\CampaignSubscriber;
use MauticPlugin\CustomObjectsBundle\Model\CustomFieldValueModel;
use MauticPlugin\CustomObjectsBundle\Model\CustomItemModel;
use MauticPlugin\CustomObjectsBundle\Tests\Functional\DataFixtures\Traits\CustomObjectsTrait;

class CampaignSubscriberTest extends MauticMysqlTestCase
{
    use CustomObjectsTrait;

    public function testVariousConditions(): void
    {
        /** @var CustomItemModel $customItemModel */
        $customItemModel       = self::$container->get('mautic.custom.model.item');

        /** @var CustomFieldValueModel $customFieldValueModel */
        $customFieldValueModel       = self::$container->get('mautic.custom.model.field.value');

        /** @var CampaignSubscriber $campaignSubscriber */
        $campaignSubscriber       = self::$container->get('custom_item.campaign.subscriber');

        $contact      = $this->createContact('john@doe.email');
        $customObject = $this->createCustomObjectWithAllFields(self::$container, 'Campaign test object');
        $customItem   = new CustomItem($customObject);

        $customItem->setName('Campaign test item');

        $customFieldValueModel->createValuesForItem($customItem);

        // Set some values
        $textValue = $customItem->findCustomFieldValueForFieldAlias('text-test-field');
        $textValue->setValue('abracadabra');

        $dateValue = $customItem->findCustomFieldValueForFieldAlias('date-test-field');
        $dateValue->setValue('2019-07-17');

        $datetimeValue = $customItem->findCustomFieldValueForFieldAlias('datetime-test-field');
        $datetimeValue->setValue('2019-07-17 12:44:55');

        $multiselectValue = $customItem->findCustomFieldValueForFieldAlias('multiselect-test-field');
        $multiselectValue->setValue(['option_b']);

        $urlValue = $customItem->findCustomFieldValueForFieldAlias('url-test-field');

        // Save the values
        $customItem = $customItemModel->save($customItem);
        $customItemModel->linkEntity($customItem, 'contact', (int) $contact->getId());

        // Test the multiselect value less than 2019-08-05.
        // This is throwing 'Array to string conversion' exception. Have to investigate further.
        $event = $this->createCampaignExecutionEvent(
            $contact,
            $multiselectValue->getCustomField()->getId(),
            'in',
            'option_b'
        );

        $campaignSubscriber->onCampaignTriggerCondition($event);
        $this->assertTrue($event->getResult());

        // Test the multiselect value less than 2019-06-05.
        $event = $this->createCampaignExecutionEvent(
            $contact,
            $multiselectValue->getCustomField()->getId(),
            'in',
            ['option_a']
        );

        $campaignSubscriber->onCampaignTriggerCondition($event);
        $this->assertFalse($event->getResult());

        // Test the URL value is empty.
        $event = $this->createCampaignExecutionEvent(
            $contact,
            $urlValue->getCustomField()->getId(),
            'empty'
        );

        $campaignSubscriber->onCampaignTriggerCondition($event);
        $this->assertTrue($event->getResult());

        // Test the URL value is not empty.
        $event = $this->createCampaignExecutionEvent(
            $contact,
            $urlValue->getCustomField()->getId(),
            '!empty'
        );

        $campaignSubscriber->onCampaignTriggerCondition($event);
        $this->assertFalse($event->getResult());

        // Test equals the same text as the field value.
        $event = $this->createCampaignExecutionEvent(
            $contact,
            $textValue->getCustomField()->getId(),
            '=',
            'abracadabra'
        );

        $campaignSubscriber->onCampaignTriggerCondition($event);
        $this->assertTrue($event->getResult());

        // Test equals the different text as the field value.
        $event = $this->createCampaignExecutionEvent(
            $contact,
            $textValue->getCustomField()->getId(),
            '=',
            'unicorn'
        );

        $campaignSubscriber->onCampaignTriggerCondition($event);
        $this->assertFalse($event->getResult());

        // Test not equals the different text as the field value.
        $event = $this->createCampaignExecutionEvent(
            $contact,
            $textValue->getCustomField()->getId(),
            '!=',
            'unicorn'
        );

        $campaignSubscriber->onCampaignTriggerCondition($event);
        $this->assertTrue($event->getResult());

        // Test the text value is empty.
        $event = $this->createCampaignExecutionEvent(
            $contact,
            $textValue->getCustomField()->getId(),
            'empty'
        );

        $campaignSubscriber->onCampaignTriggerCondition($event);
        $this->assertFalse($event->getResult());

        // Test the text value is not empty.
        $event = $this->createCampaignExecutionEvent(
            $contact,
            $textValue->getCustomField()->getId(),
            '!empty'
        );

        $campaignSubscriber->onCampaignTriggerCondition($event);
        $this->assertTrue($event->getResult());

        // Test the text value starts with abra.
        $event = $this->createCampaignExecutionEvent(
            $contact,
            $textValue->getCustomField()->getId(),
            'startsWith',
            'abra'
        );

        $campaignSubscriber->onCampaignTriggerCondition($event);
        $this->assertTrue($event->getResult());

        // Test the text value starts with unicorn.
        $event = $this->createCampaignExecutionEvent(
            $contact,
            $textValue->getCustomField()->getId(),
            'startsWith',
            'unicorn'
        );

        $campaignSubscriber->onCampaignTriggerCondition($event);
        $this->assertFalse($event->getResult());

        // Test the text value ends with abra.
        $event = $this->createCampaignExecutionEvent(
            $contact,
            $textValue->getCustomField()->getId(),
            'endsWith',
            'cadabra'
        );

        $campaignSubscriber->onCampaignTriggerCondition($event);
        $this->assertTrue($event->getResult());

        // Test the text value emnds with unicorn.
        $event = $this->createCampaignExecutionEvent(
            $contact,
            $textValue->getCustomField()->getId(),
            'endsWith',
            'unicorn'
        );

        $campaignSubscriber->onCampaignTriggerCondition($event);
        $this->assertFalse($event->getResult());

        // Test the text value contains cada.
        $event = $this->createCampaignExecutionEvent(
            $contact,
            $textValue->getCustomField()->getId(),
            'contains',
            'cada'
        );

        $campaignSubscriber->onCampaignTriggerCondition($event);
        $this->assertTrue($event->getResult());

        // Test the text value contains unicorn.
        $this->createCampaignExecutionEvent(
            $contact,
            $textValue->getCustomField()->getId(),
            'contains',
            'unicorn'
        );

        // Test the text value like abra%.
        $event = $this->createCampaignExecutionEvent(
            $contact,
            $textValue->getCustomField()->getId(),
            'like',
            'abra%'
        );

        $campaignSubscriber->onCampaignTriggerCondition($event);
        $this->assertTrue($event->getResult());

        // Test the text value not like unicorn.
        $event = $this->createCampaignExecutionEvent(
            $contact,
            $textValue->getCustomField()->getId(),
            '!like',
            'unicorn'
        );

        $campaignSubscriber->onCampaignTriggerCondition($event);
        $this->assertFalse($event->getResult());

        // Test the date value less than 2019-08-05.
        $event = $this->createCampaignExecutionEvent(
            $contact,
            $dateValue->getCustomField()->getId(),
            'lt',
            '2019-08-05'
        );

        $campaignSubscriber->onCampaignTriggerCondition($event);
        $this->assertTrue($event->getResult());

        // Test the date value less than 2019-06-05.
        $event = $this->createCampaignExecutionEvent(
            $contact,
            $dateValue->getCustomField()->getId(),
            'lt',
            '2019-06-05'
        );

        $campaignSubscriber->onCampaignTriggerCondition($event);
        $this->assertFalse($event->getResult());

        // Test the date value greater than 2019-08-05.
        $event = $this->createCampaignExecutionEvent(
            $contact,
            $dateValue->getCustomField()->getId(),
            'gt',
            '2019-08-05'
        );

        $campaignSubscriber->onCampaignTriggerCondition($event);
        $this->assertFalse($event->getResult());

        // Test the date value greater than 2019-06-05.
        $event = $this->createCampaignExecutionEvent(
            $contact,
            $dateValue->getCustomField()->getId(),
            'gt',
            '2019-06-05'
        );

        $campaignSubscriber->onCampaignTriggerCondition($event);
        $this->assertTrue($event->getResult());
    }

    /**
     * @param mixed $fieldValue
     */
    private function createCampaignExecutionEvent(
        Lead $contact,
        int $fieldId,
        string $operator,
        $fieldValue = null
    ): CampaignExecutionEvent {
        return new CampaignExecutionEvent(
            [
                'lead'  => $contact,
                'event' => [
                    'type'       => 'custom_item.1.fieldvalue',
                    'properties' => [
                        'field'    => $fieldId,
                        'operator' => $operator,
                        'value'    => $fieldValue,
                    ],
                ],
                'eventDetails'    => [],
                'systemTriggered' => [],
                'eventSettings'   => [],
            ],
            null
        );
    }

    private function createContact(string $email): Lead
    {
        /** @var LeadModel $contactModel */
        $contactModel = self::$container->get('mautic.lead.model.lead');
        $contact      = new Lead();
        $contact->setEmail($email);
        $contactModel->saveEntity($contact);

        return $contact;
    }
}
