<?php

declare(strict_types=1);

namespace MauticPlugin\CustomObjectsBundle\Tests\Functional\EventListener;

use Mautic\CoreBundle\Helper\CommandHelper;
use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Entity\LeadField;
use Mautic\LeadBundle\Entity\LeadList;
use Mautic\LeadBundle\Entity\ListLead;
use MauticPlugin\CustomObjectsBundle\CustomFieldType\CustomFieldTypeInterface;
use MauticPlugin\CustomObjectsBundle\Entity\CustomField;
use MauticPlugin\CustomObjectsBundle\Entity\CustomItem;
use MauticPlugin\CustomObjectsBundle\Entity\CustomObject;
use MauticPlugin\CustomObjectsBundle\Provider\CustomFieldTypeProvider;
use MauticPlugin\CustomObjectsBundle\Tests\ProjectVersionTrait;
use Symfony\Component\HttpFoundation\Request;

class FilterOperatorSubscriberTest extends MauticMysqlTestCase
{
    use ProjectVersionTrait;

    /**
     * @var CommandHelper
     */
    private $commandHelper;

    protected function setUp(): void
    {
        parent::setUp();
        $this->commandHelper = static::getContainer()->get('mautic.helper.command');
    }

    public function testIfNewOperatorNotInCustomObjectsAddedinSegmentFilter()
    {
        $crawler         = $this->client->request(Request::METHOD_GET, '/s/segments/new/');
        $segment_filters = $crawler->filter('#available_segment_filters')->html();
        $this->assertStringContainsString('not in custom objects', $segment_filters);
    }

    public function testIfProperContactsAreAddedinSegmentWithNotInCustomObjectsFilter()
    {
        // 1) create 2 contacts with "testcontact1@acquia.com" and "testcontact2@gmail.com" in email
        $contact1 = new Lead();
        $contact1->setFirstname('Test')->setLastname('Contact 1')->setEmail('testcontact1@acquia.com');
        $this->em->persist($contact1);

        $contact2 = new Lead();
        $contact2->setFirstname('Test')->setLastname('Contact 2')->setEmail('testcontact2@gmail.com');
        $this->em->persist($contact2);

        // 2) create custom object "Email List" with "testcontact1@acquia.com" and "testcontact2@mautic.com" as items
        $customObject = new CustomObject();
        $customObject->setNameSingular('Email List');
        $customObject->setNamePlural('Emai List');
        $customObject->setAlias('emails');
        $customObject->setType(CustomObject::TYPE_MASTER);
        $this->em->persist($customObject);

        $customItem1   = new CustomItem($customObject);
        $customItem1->setName('testcontact1@acquia.com');
        $this->em->persist($customItem1);

        $customItem2   = new CustomItem($customObject);
        $customItem2->setName('testcontact2@mautic.com');
        $this->em->persist($customItem2);
        $this->em->flush();

        // 3) create a segment with filter : email > not in custom objects > select custom object
        $filters = [[
            'object'     => 'lead',
            'glue'       => 'and',
            'field'      => 'email',
            'type'       => 'text',
            'operator'   => 'notInCustomObjects',
            'properties' => [
                'filter' => 'custom-object:'.$customObject->getId().':name',
            ],
            'filter'     => 'custom-object:'.$customObject->getId().':name',
            'display'    => null,
        ]];
        $segment = new LeadList();
        $segment->setName('Test Segment A');
        $segment->setPublicName('Test Segment A');
        $segment->setAlias('test-segment-a');
        $segment->setFilters($filters);
        $this->em->persist($segment);
        $this->em->flush();

        // 4) run update segment command
        $this->commandHelper->runCommand('mautic:segments:update', ['-i' => $segment->getId()]);

        // 5) fetch segment added contacts
        $leads = $this->em->getRepository(ListLead::class)->findBy(['list' => $segment->getId()], ['lead' => 'DESC']);

        // 6) Assertions
        $this->assertCount(1, $leads);
        $this->assertSame($contact2->getId(), $leads[0]->getLead()->getId());
        $this->assertSame($contact2->getFirstname(), $leads[0]->getLead()->getFirstname());
        $this->assertSame($contact2->getLastname(), $leads[0]->getLead()->getLastname());
        $this->assertSame($contact2->getEmail(), $leads[0]->getLead()->getEmail());
    }

    public function testCustomObjectSegmentFilterOperatorForDateField(): void
    {
        if (!$this->isCloudProject()) {
            $this->markTestSkipped('As context is not available for segment only in 4.4');
        }

        $leadField = $this->createField('date_field', 'date');

        $fieldTypeProvider = self::$container->get('custom_field.type.provider');
        \assert($fieldTypeProvider instanceof CustomFieldTypeProvider);
        $objectType = $fieldTypeProvider->getType('date');
        $dateField  = $this->createCustomField('co_date_field', $objectType);

        $this->createCustomObject('obj', $dateField);

        $this->em->flush();
        $crawler = $this->client->request(Request::METHOD_GET, '/s/segments/new/');

        $coDateFilterOperators = $crawler
            ->filter('#available_segment_filters option[id="available_custom_object_cmf_'.$dateField->getId().'"]')
            ->attr('data-field-operators');

        $leadDateFilterOperators = $crawler
            ->filter('#available_segment_filters option[id="available_lead_'.$leadField->getAlias().'"]')
            ->attr('data-field-operators');

        $this->assertSame($coDateFilterOperators, $leadDateFilterOperators);
    }

    private function createField(string $alias, string $type): LeadField
    {
        $field = new LeadField();
        $field->setName($alias);
        $field->setAlias($alias);
        $field->setType($type);
        $this->em->persist($field);

        return $field;
    }

    private function createCustomObject(string $alias, ?CustomField $dateField = null): CustomObject
    {
        $customObject = new CustomObject();
        $customObject->setNameSingular($alias);
        $customObject->setNamePlural($alias.'s');
        $customObject->setAlias($alias);
        if ($dateField) {
            $customObject->addCustomField($dateField);
        }
        $this->em->persist($customObject);

        return $customObject;
    }

    private function createCustomField(string $alias, CustomFieldTypeInterface $objectType): CustomField
    {
        $dateField  = new CustomField();
        $dateField->setTypeObject($objectType);
        $dateField->setType($objectType->getKey());
        $dateField->setLabel($alias);
        $dateField->setAlias($alias);

        return $dateField;
    }
}
