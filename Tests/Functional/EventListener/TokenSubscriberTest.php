<?php

declare(strict_types=1);

namespace MauticPlugin\CustomObjectsBundle\Tests\Functional\EventListener;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\EmailBundle\Entity\Email;
use Mautic\EmailBundle\Event\EmailSendEvent;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Entity\LeadList;
use Mautic\LeadBundle\Model\LeadModel;
use Mautic\LeadBundle\Model\ListModel;
use MauticPlugin\CustomObjectsBundle\Entity\CustomItem;
use MauticPlugin\CustomObjectsBundle\EventListener\TokenSubscriber;
use MauticPlugin\CustomObjectsBundle\Model\CustomFieldValueModel;
use MauticPlugin\CustomObjectsBundle\Model\CustomItemModel;
use MauticPlugin\CustomObjectsBundle\Tests\Functional\DataFixtures\Traits\CustomObjectsTrait;

class TokenSubscriberTest extends MauticMysqlTestCase
{
    use CustomObjectsTrait;

    /**
     * @var CustomItemModel
     */
    private $customItemModel;

    /**
     * @var CustomFieldValueModel
     */
    private $customFieldValueModel;

    /**
     * @var TokenSubscriber
     */
    private $subscriber;

    protected function setUp(): void
    {
        parent::setUp();

        $this->customItemModel       = self::$container->get('mautic.custom.model.item');
        $this->customFieldValueModel = self::$container->get('mautic.custom.model.field.value');
        $this->subscriber            = self::$container->get('custom_object.emailtoken.subscriber');
    }

    public function testTextFieldSegmentFilterToken(): void
    {
        $customObject = $this->createCustomObjectWithAllFields(self::$container, 'Product');
        $customItem   = new CustomItem($customObject);
        $contact      = $this->createContact('john@doe.email');

        $customItem->setName('Light bulb');

        $this->customFieldValueModel->createValuesForItem($customItem);

        $textValue       = $customItem->findCustomFieldValueForFieldAlias('text-test-field');
        $urlValue        = $customItem->findCustomFieldValueForFieldAlias('url-test-field');
        $mutiselectValue = $customItem->findCustomFieldValueForFieldAlias('multiselect-test-field');

        $textValue->setValue('abracadabra');
        $mutiselectValue->setValue('option_b');

        $customItem = $this->customItemModel->save($customItem);
        $this->customItemModel->linkEntity($customItem, 'contact', (int) $contact->getId());

        $filters = [
            [
                'glue'     => 'and',
                'field'    => 'cmf_'.$textValue->getCustomField()->getId(),
                'object'   => 'custom_object',
                'type'     => 'text',
                'filter'   => 'abracadabra',
                'display'  => null,
                'operator' => '=',
            ],
            [
                'glue'     => 'and',
                'field'    => 'cmf_'.$urlValue->getCustomField()->getId(),
                'object'   => 'custom_object',
                'type'     => 'text',
                'filter'   => '',
                'display'  => null,
                'operator' => 'empty',
            ],
        ];
        $segment = $this->createSegment($filters);
        $this->addContactToSegment($contact, $segment);

        $html = '<!DOCTYPE html>
        <html>
        <head>
        <title>{subject}</title>
        </head>
        <body>
        Hello, here is the thing:
        {custom-object=products:text-test-field | where=segment-filter |order=latest|limit=1 | default=No thing} 
        {custom-object=products:multiselect-test-field | where=segment-filter |order=latest|limit=1 | default=No thing} 
        Regards
        </body>
        </html>
        ';

        $email = new Email();
        $email->setEmailType('list');
        $email->addList($segment);
        $event = new EmailSendEvent(
            null,
            [
                'subject'          => 'CO segment test',
                'content'          => $html,
                'conplainTexttent' => '',
                'email'            => $email,
                'lead'             => ['id' => $contact->getId(), 'email' => $contact->getEmail()],
                'source'           => null,
            ]
        );

        $this->subscriber->decodeTokens($event);

        $this->assertSame(
            [
                '{custom-object=products:text-test-field | where=segment-filter |order=latest|limit=1 | default=No thing}'        => 'abracadabra',
                '{custom-object=products:multiselect-test-field | where=segment-filter |order=latest|limit=1 | default=No thing}' => '"Option B"',
            ],
            $event->getTokens()
        );
    }

    public function testDatetimeFieldSegmentFilterToken(): void
    {
        $customObject = $this->createCustomObjectWithAllFields(self::$container, 'Product');
        $customItem   = new CustomItem($customObject);
        $contact      = $this->createContact('john@doe.email');

        $customItem->setName('Light bulb');

        $this->customFieldValueModel->createValuesForItem($customItem);

        $value = $customItem->findCustomFieldValueForFieldAlias('datetime-test-field');

        $value->setValue('2019-07-17 13:00:00');

        $customItem = $this->customItemModel->save($customItem);
        $this->customItemModel->linkEntity($customItem, 'contact', (int) $contact->getId());

        $filters = [
            [
                'glue'     => 'and',
                'field'    => 'cmf_'.$value->getCustomField()->getId(),
                'object'   => 'custom_object',
                'type'     => 'datetime',
                'filter'   => '2019-07-16 13:00:00',
                'display'  => null,
                'operator' => 'gt',
            ],
        ];
        $segment = $this->createSegment($filters);
        $this->addContactToSegment($contact, $segment);

        $html = '<!DOCTYPE html>
        <html>
        <head>
        <title>{subject}</title>
        </head>
        <body>
        Hello, here is the thing:
        {custom-object=products:datetime-test-field | where=segment-filter |order=latest|limit=1 | default=No thing} 
        Regards
        </body>
        </html>
        ';

        $email = new Email();
        $email->setEmailType('list');
        $email->addList($segment);
        $event = new EmailSendEvent(
            null,
            [
                'subject'          => 'CO segment test',
                'content'          => $html,
                'conplainTexttent' => '',
                'email'            => $email,
                'lead'             => ['id' => $contact->getId(), 'email' => $contact->getEmail()],
                'source'           => null,
            ]
        );

        $this->subscriber->decodeTokens($event);

        $this->assertSame(
            [
                '{custom-object=products:datetime-test-field | where=segment-filter |order=latest|limit=1 | default=No thing}' => '2019-07-17 13:00:00',
            ],
            $event->getTokens()
        );
    }

    public function testMultiselectFieldSegmentFilterToken(): void
    {
        $customObject = $this->createCustomObjectWithAllFields(self::$container, 'Product');
        $customItem   = new CustomItem($customObject);
        $contact      = $this->createContact('john@doe.email');

        $customItem->setName('Light bulb');

        $this->customFieldValueModel->createValuesForItem($customItem);

        $value = $customItem->findCustomFieldValueForFieldAlias('multiselect-test-field');

        $value->setValue(['option_a', 'option_b']);

        $customItem = $this->customItemModel->save($customItem);
        $this->customItemModel->linkEntity($customItem, 'contact', (int) $contact->getId());

        $filters = [
            [
                'glue'     => 'and',
                'field'    => 'cmf_'.$value->getCustomField()->getId(),
                'object'   => 'custom_object',
                'type'     => 'multiselect',
                'filter'   => ['option_b'],
                'display'  => null,
                'operator' => 'in',
            ],
        ];
        $segment = $this->createSegment($filters);
        $this->addContactToSegment($contact, $segment);

        $html = '<!DOCTYPE html>
        <html>
        <head>
        <title>{subject}</title>
        </head>
        <body>
        Hello, here is the thing:
        {custom-object=products:multiselect-test-field | where=segment-filter |order=latest|limit=1 | default=No thing} 
        Regards
        </body>
        </html>
        ';

        $email = new Email();
        $email->setEmailType('list');
        $email->addList($segment);
        $event = new EmailSendEvent(
            null,
            [
                'subject'          => 'CO segment test',
                'content'          => $html,
                'conplainTexttent' => '',
                'email'            => $email,
                'lead'             => ['id' => $contact->getId(), 'email' => $contact->getEmail()],
                'source'           => null,
            ]
        );

        $this->subscriber->decodeTokens($event);

        $this->assertSame(
            [
                '{custom-object=products:multiselect-test-field | where=segment-filter |order=latest|limit=1 | default=No thing}' => '"Option A","Option B"',
            ],
            $event->getTokens()
        );
    }

    public function testSelectFieldSegmentFilterToken(): void
    {
        $customObject = $this->createCustomObjectWithAllFields(self::$container, 'Product');
        $customItem   = new CustomItem($customObject);
        $contact      = $this->createContact('john@doe.email');

        $customItem->setName('Light bulb');

        $this->customFieldValueModel->createValuesForItem($customItem);

        $value = $customItem->findCustomFieldValueForFieldAlias('select-test-field');

        $value->setValue('option_b');

        $customItem = $this->customItemModel->save($customItem);
        $this->customItemModel->linkEntity($customItem, 'contact', (int) $contact->getId());

        $filters = [
            [
                'glue'     => 'and',
                'field'    => 'cmf_'.$value->getCustomField()->getId(),
                'object'   => 'custom_object',
                'type'     => 'select',
                'filter'   => 'option_b',
                'display'  => null,
                'operator' => 'in',
            ],
        ];
        $segment = $this->createSegment($filters);
        $this->addContactToSegment($contact, $segment);

        $html = '<!DOCTYPE html>
        <html>
        <head>
        <title>{subject}</title>
        </head>
        <body>
        Hello, here is the thing:
        {custom-object=products:select-test-field | where=segment-filter |order=latest|limit=1 | default=No thing} 
        Regards
        </body>
        </html>
        ';

        $email = new Email();
        $email->setEmailType('list');
        $email->addList($segment);
        $event = new EmailSendEvent(
            null,
            [
                'subject'          => 'CO segment test',
                'content'          => $html,
                'conplainTexttent' => '',
                'email'            => $email,
                'lead'             => ['id' => $contact->getId(), 'email' => $contact->getEmail()],
                'source'           => null,
            ]
        );

        $this->subscriber->decodeTokens($event);

        $this->assertSame(
            [
                '{custom-object=products:select-test-field | where=segment-filter |order=latest|limit=1 | default=No thing}' => 'Option B',
            ],
            $event->getTokens()
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

    /**
     * @param mixed[] $filters
     */
    private function createSegment(array $filters): LeadList
    {
        /** @var ListModel $segmentModel */
        $segmentModel = self::$container->get('mautic.lead.model.list');
        $segment      = new LeadList();
        $segment->setFilters($filters);
        $segment->setName('Segment A');
        $segmentModel->saveEntity($segment);

        return $segment;
    }

    private function addContactToSegment(Lead $contact, LeadList $segment): void
    {
        /** @var ListModel $segmentModel */
        $segmentModel = self::$container->get('mautic.lead.model.list');
        $segmentModel->addLead($contact, $segment, true);
    }
}
